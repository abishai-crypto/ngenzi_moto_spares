<?php
/**
 * NGENZI MOTO SPARE PARTS
 * OnlineOrder Model (Phase 16)
 * Atomic Online Checkout, Concurrency Protection, Sales & Inventory Integration
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Inventory;
use App\Models\Sale;
use App\Models\Setting;
use App\Models\CustomerAddress;
use PDO;
use Exception;

// Ensure service classes can be resolved in non-autoloaded CLI test environments
if (!class_exists('App\\Services\\Delivery\\DeliveryManager', false)) {
    if (file_exists(__DIR__ . '/../services/delivery/DeliveryProviderInterface.php')) {
        require_once __DIR__ . '/../services/delivery/DeliveryProviderInterface.php';
    }
    if (file_exists(__DIR__ . '/../services/delivery/DeliveryManager.php')) {
        require_once __DIR__ . '/../services/delivery/DeliveryManager.php';
    }
}
if (!class_exists('App\\Services\\Notification\\NotificationManager', false)) {
    if (file_exists(__DIR__ . '/../services/notification/NotificationManager.php')) {
        require_once __DIR__ . '/../services/notification/NotificationManager.php';
    }
}

class OnlineOrder {
    /**
     * Generate concurrency-safe sequential order number (e.g. ORD-000001)
     */
    public static function generateOrderNumber(?PDO $pdo = null): string {
        $pdo = $pdo ?? Database::getConnection();
        $prefix = 'ORD-';

        $stmt = $pdo->query("SELECT MAX(id) FROM `online_orders`");
        $maxId = (int)$stmt->fetchColumn();
        $nextNum = $maxId + 1;

        do {
            $candidate = sprintf("%s%06d", $prefix, $nextNum);
            $check = $pdo->prepare("SELECT id FROM `online_orders` WHERE order_number = ?");
            $check->execute([$candidate]);
            if (!$check->fetchColumn()) {
                return $candidate;
            }
            $nextNum++;
        } while (true);
    }

    /**
     * Atomic Checkout Workflow inside an ACID Database Transaction
     * Locks products with SELECT ... FOR UPDATE, recalculates all prices server-side,
     * creates online order, line items, order status history, delivery record,
     * and deducts inventory safely.
     */
    public static function createOrder(
        int $customerId,
        int $addressId,
        string $paymentMethod = 'Mobile Money',
        string $deliveryMethod = 'STORE_DELIVERY',
        string $customerNotes = '',
        string $deliveryProvider = '',
        array $deliveryDetails = []
    ): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Resolve provider code if not explicitly given
            if (empty($deliveryProvider)) {
                $deliveryProvider = \App\Services\Delivery\DeliveryManager::resolveProvider($deliveryMethod)->getCode();
            } else {
                $deliveryProvider = strtoupper(trim($deliveryProvider));
            }

            // 1. Verify customer status
            $stmtCustomer = $pdo->prepare("SELECT id, name, phone, email, status FROM `customers` WHERE id = ?");
            $stmtCustomer->execute([$customerId]);
            $customer = $stmtCustomer->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                throw new Exception("Taarifa za mteja hazikupatikana.");
            }
            if ($customer['status'] === 'blocked') {
                throw new Exception("Akaunti yako imezuiwa (Blocked). Huwezi kufanya manunuzi ya mtandaoni.");
            }

            // 2. Fetch and snapshot delivery address
            $address = CustomerAddress::find($addressId, $customerId);
            if (!$address) {
                throw new Exception("Anwani ya kuwasilisha mzigo haikupatikana au si yako.");
            }

            $addressSnapshot = json_encode([
                'recipient_name' => $address['recipient_name'],
                'phone'          => $address['phone'],
                'region'         => $address['region'],
                'district'       => $address['district'],
                'ward'           => $address['ward'] ?? '',
                'street'         => $address['street'] ?? '',
                'address'        => $address['address'],
                'landmark'       => $address['landmark'] ?? '',
                'latitude'       => $address['latitude'] ?? null,
                'longitude'      => $address['longitude'] ?? null,
            ], JSON_UNESCAPED_UNICODE);

            // 3. Fetch cart items
            $cartItems = Cart::getItems();
            if (empty($cartItems)) {
                throw new Exception("Kapu lako halina bidhaa (Cart is empty).");
            }

            // 4. Concurrency Protection & Server-Side Price Verification
            $processedItems = [];
            $computedSubtotal = 0.00;
            $computedTotalCogs = 0.00;

            foreach ($cartItems as $cItem) {
                $productId = (int)$cItem['product_id'];
                $qty = (int)$cItem['quantity'];

                if ($qty <= 0) {
                    continue;
                }

                // Row-lock product
                $stmtLock = $pdo->prepare("
                    SELECT id, name, sku, buying_price, selling_price, current_stock, status, is_online, is_catalog_only
                    FROM `products`
                    WHERE id = ?
                    FOR UPDATE
                ");
                $stmtLock->execute([$productId]);
                $product = $stmtLock->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("Kipuri #{$productId} hakikupatikana.");
                }
                if ($product['status'] !== 'active' || (int)$product['is_online'] !== 1) {
                    throw new Exception("Kipuri '{$product['name']}' hakiruhusiwi kwa mauzo ya mtandaoni kwa sasa.");
                }
                if (!empty($product['is_catalog_only'])) {
                    throw new Exception("Kipuri '{$product['name']}' kipo kwenye katalogi ya marejeleo tu na hakipatikani kwa manunuzi ya moja kwa moja.");
                }

                $availableStock = (int)$product['current_stock'];
                if ($qty > $availableStock) {
                    throw new Exception("Stoo haitoshi kwa ajili ya '{$product['name']}'. Kilichopo stoo ni {$availableStock}, ulichoomba: {$qty}.");
                }

                $unitPrice = (float)$product['selling_price'];
                $unitCost = (float)$product['buying_price']; // Historical COGS
                $itemSubtotal = $unitPrice * $qty;
                $itemTotalCost = $unitCost * $qty;
                $itemGrossProfit = $itemSubtotal - $itemTotalCost;

                $computedSubtotal += $itemSubtotal;
                $computedTotalCogs += $itemTotalCost;

                $processedItems[] = [
                    'product_id'   => $productId,
                    'product_name' => $product['name'],
                    'product_sku'  => $product['sku'],
                    'quantity'     => $qty,
                    'unit_cost'    => $unitCost,
                    'unit_price'   => $unitPrice,
                    'subtotal'     => $itemSubtotal,
                    'total_cost'   => $itemTotalCost,
                    'gross_profit' => $itemGrossProfit
                ];
            }

            if (empty($processedItems)) {
                throw new Exception("Hakuna bidhaa zilizokidhi vigezo vya kuagizwa.");
            }

            // 5. Dynamic Delivery Fee Calculation via DeliveryManager (Phase 17)
            $deliveryFee = \App\Services\Delivery\DeliveryManager::calculateFee($deliveryProvider, $computedSubtotal);

            // Grand Total Formula: grand_total = subtotal - discount + delivery_fee
            $grandTotal = $computedSubtotal + $deliveryFee;
            $orderNumber = self::generateOrderNumber($pdo);

            // 6. Insert `online_orders`
            $stmtOrder = $pdo->prepare("
                INSERT INTO `online_orders` (
                    order_number, customer_id, status, subtotal, discount_amount,
                    delivery_fee, total_amount, payment_method, payment_status,
                    delivery_method, delivery_provider, delivery_reference,
                    delivery_address_id, delivery_address_snapshot,
                    customer_notes, created_at
                ) VALUES (
                    ?, ?, 'PENDING', ?, 0.00,
                    ?, ?, ?, 'unpaid',
                    ?, ?, ?,
                    ?, ?,
                    ?, NOW()
                )
            ");

            $extRef = $deliveryDetails['external_reference'] ?? null;
            $stmtOrder->execute([
                $orderNumber,
                $customerId,
                $computedSubtotal,
                $deliveryFee,
                $grandTotal,
                $paymentMethod,
                $deliveryMethod,
                $deliveryProvider,
                $extRef,
                $addressId,
                $addressSnapshot,
                $customerNotes ?: null
            ]);

            $orderId = (int)$pdo->lastInsertId();

            // 7. Insert `online_order_items`
            $stmtItem = $pdo->prepare("
                INSERT INTO `online_order_items` (
                    order_id, product_id, product_name, product_sku,
                    quantity, unit_cost, unit_price, subtotal,
                    total_cost, gross_profit, created_at
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, NOW()
                )
            ");

            foreach ($processedItems as $pi) {
                $stmtItem->execute([
                    $orderId,
                    $pi['product_id'],
                    $pi['product_name'],
                    $pi['product_sku'],
                    $pi['quantity'],
                    $pi['unit_cost'],
                    $pi['unit_price'],
                    $pi['subtotal'],
                    $pi['total_cost'],
                    $pi['gross_profit']
                ]);

                // Deduct stock safely with inventory transaction
                Inventory::recordMovement(
                    $pi['product_id'],
                    'sale',
                    -$pi['quantity'],
                    'online_order',
                    $orderId,
                    "Agizo la mtandaoni #{$orderNumber}",
                    $pi['unit_cost'],
                    null,
                    $pdo
                );
            }

            // 8. Create Delivery record with flexible provider fields (Phase 17)
            $deliveryNum = sprintf("DEL-%06d", $orderId);
            $stmtDel = $pdo->prepare("
                INSERT INTO `deliveries` (
                    delivery_number, order_id, customer_id, address_id,
                    recipient_name, recipient_phone, delivery_method, delivery_provider,
                    external_reference, courier_name, courier_phone, courier_instructions,
                    pickup_instructions, delivery_fee, delivery_status, created_at
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, 'PROVIDER_SELECTED', NOW()
                )
            ");
            $stmtDel->execute([
                $deliveryNum,
                $orderId,
                $customerId,
                $addressId,
                $address['recipient_name'],
                $address['phone'],
                $deliveryMethod,
                $deliveryProvider,
                $extRef,
                $deliveryDetails['courier_name'] ?? null,
                $deliveryDetails['courier_phone'] ?? null,
                $deliveryDetails['courier_instructions'] ?? null,
                $deliveryDetails['pickup_instructions'] ?? null,
                $deliveryFee
            ]);
            $deliveryId = (int)$pdo->lastInsertId();

            // 9. Record initial order status history
            $stmtHist = $pdo->prepare("
                INSERT INTO `order_status_history` (order_id, old_status, new_status, comment, created_at)
                VALUES (?, NULL, 'PENDING', ?, NOW())
            ");
            $stmtHist->execute([$orderId, "Agizo jipya la mtandaoni limetengenezwa na mteja (Njia: {$deliveryProvider})"]);

            // 10. Record initial delivery status history
            $pdo->prepare("
                INSERT INTO `delivery_status_history` (delivery_id, old_status, new_status, notes, created_at)
                VALUES (?, NULL, 'PROVIDER_SELECTED', ?, NOW())
            ")->execute([$deliveryId, "Njia ya usafirishaji imechaguliwa: {$deliveryProvider}"]);

            // 11. Clear shopping cart
            Cart::clear();

            // 12. Multi-channel Customer Notification (Phase 17)
            \App\Services\Notification\NotificationManager::sendOrderNotification([
                'id'                => $orderId,
                'customer_id'       => $customerId,
                'order_number'      => $orderNumber,
                'delivery_provider' => $deliveryProvider,
                'delivery_method'   => $deliveryMethod,
                'delivery_status'   => 'PROVIDER_SELECTED',
                'payment_status'    => 'unpaid',
                'total_amount'      => $grandTotal
            ]);

            // Commit transaction
            $pdo->commit();

            Helper::logAudit('ONLINE_ORDER_PLACED', 'ONLINE_STORE', $orderId, null, [
                'order_number' => $orderNumber,
                'customer_id'  => $customerId,
                'total_amount' => $grandTotal,
                'items_count'  => count($processedItems)
            ]);

            return [
                'success'      => true,
                'order_id'     => $orderId,
                'order_number' => $orderNumber,
                'total_amount' => $grandTotal,
                'subtotal'     => $computedSubtotal,
                'delivery_fee' => $deliveryFee,
                'items'        => $processedItems,
                'delivery_num' => $deliveryNum
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Integrate completed/confirmed online order into internal `sales` system
     */
    public static function integrateWithSales(int $orderId, ?int $approvedByUserId = null): int {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT oo.*, c.name as customer_name, c.phone as customer_phone
            FROM `online_orders` oo
            JOIN `customers` c ON oo.customer_id = c.id
            WHERE oo.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Agizo halikupatikana.");
        }

        if (!empty($order['sale_id'])) {
            return (int)$order['sale_id']; // Already integrated
        }

        // Generate sales invoice
        $invoiceNo = Sale::generateInvoiceNo();
        $discountAmount = (float)($order['discount_amount'] ?? 0.00);
        $deliveryFee = (float)($order['delivery_fee'] ?? 0.00);
        $productSubtotal = (float)$order['subtotal'];
        $totalAmount = (float)($order['total_amount'] ?? ($productSubtotal - $discountAmount + $deliveryFee));

        // Get total cogs from order items
        $stmtCogs = $pdo->prepare("SELECT SUM(total_cost) as total_cogs, SUM(gross_profit) as gross_profit FROM `online_order_items` WHERE order_id = ?");
        $stmtCogs->execute([$orderId]);
        $financials = $stmtCogs->fetch(PDO::FETCH_ASSOC);

        $totalCogs = (float)($financials['total_cogs'] ?? 0.00);
        $grossProfit = (float)($financials['gross_profit'] ?? ($productSubtotal - $totalCogs));
        $grossMarginPercent = ($productSubtotal > 0) ? round(($grossProfit / $productSubtotal) * 100, 2) : 0.00;

        $paidAmount = ($order['payment_status'] === 'paid') ? $totalAmount : 0.00;
        $balanceDue = max(0.00, $totalAmount - $paidAmount);
        $saleStatus = ($order['status'] === 'CANCELLED') ? 'cancelled' : 'completed';

        $stmtSale = $pdo->prepare("
            INSERT INTO `sales` (
                invoice_no, customer_id, customer_name, customer_phone,
                subtotal, discount_amount, total_amount, total_cogs,
                gross_profit, gross_margin_percent, paid_amount, balance_due,
                payment_method, payment_status, sale_status, cashier_id,
                sale_date, payment_terms, sales_channel, online_order_id, created_at
            ) VALUES (
                ?, ?, ?, ?,
                ?, 0.00, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?, ?,
                NOW(), 'Online Checkout', 'ONLINE', ?, NOW()
            )
        ");

        $stmtSale->execute([
            $invoiceNo,
            $order['customer_id'],
            $order['customer_name'],
            $order['customer_phone'],
            $productSubtotal,
            $totalAmount,
            $totalCogs,
            $grossProfit,
            $grossMarginPercent,
            $paidAmount,
            $balanceDue,
            $order['payment_method'],
            ($order['payment_status'] === 'paid' ? 'paid' : 'unpaid'),
            $saleStatus,
            $approvedByUserId ?? 1,
            $orderId
        ]);

        $saleId = (int)$pdo->lastInsertId();

        // Update online_orders with sale link
        $pdo->prepare("UPDATE `online_orders` SET sale_id = ?, invoice_no = ? WHERE id = ?")->execute([$saleId, $invoiceNo, $orderId]);

        // Insert sale_items for reporting consistency
        $items = $pdo->query("SELECT * FROM `online_order_items` WHERE order_id = {$orderId}")->fetchAll(PDO::FETCH_ASSOC);
        $stmtSaleItem = $pdo->prepare("
            INSERT INTO `sale_items` (
                sale_id, product_id, product_name, product_sku,
                quantity, unit_cost, unit_price, discount_per_unit,
                subtotal, total_cost, gross_profit, gross_margin_percent, created_at
            ) VALUES (
                ?, ?, ?, ?,
                ?, ?, ?, 0.00,
                ?, ?, ?, ?, NOW()
            )
        ");

        foreach ($items as $it) {
            $marginPct = ($it['subtotal'] > 0) ? round(($it['gross_profit'] / $it['subtotal']) * 100, 2) : 0.00;
            $stmtSaleItem->execute([
                $saleId,
                $it['product_id'],
                $it['product_name'],
                $it['product_sku'],
                $it['quantity'],
                $it['unit_cost'],
                $it['unit_price'],
                $it['subtotal'],
                $it['total_cost'],
                $it['gross_profit'],
                $marginPct
            ]);
        }

        // If paid, insert sale_payments record
        if ($paidAmount > 0) {
            $paymentNo = sprintf("PAY-ONL-%06d", $saleId);
            $stmtPay = $pdo->prepare("
                INSERT INTO `sale_payments` (
                    payment_no, sale_id, customer_id, amount, payment_method,
                    payment_date, received_by, notes, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    NOW(), ?, 'Malipo ya duka la mtandaoni', 'completed', NOW()
                )
            ");
            $stmtPay->execute([
                $paymentNo,
                $saleId,
                $order['customer_id'],
                $paidAmount,
                $order['payment_method'],
                $approvedByUserId ?? 1
            ]);
        }

        return $saleId;
    }

    /**
     * Find order with strict ownership authorization
     */
    public static function find(int $orderId, ?int $customerId = null): ?array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT oo.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                   d.id as delivery_id, d.delivery_number, d.delivery_status, d.tracking_notes
            FROM `online_orders` oo
            JOIN `customers` c ON oo.customer_id = c.id
            LEFT JOIN `deliveries` d ON oo.id = d.order_id
            WHERE oo.id = ?
        ";
        $params = [$orderId];

        if ($customerId !== null) {
            $sql .= " AND oo.customer_id = ?";
            $params[] = $customerId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        // Fetch items
        $stmtItems = $pdo->prepare("SELECT * FROM `online_order_items` WHERE order_id = ? ORDER BY id ASC");
        $stmtItems->execute([$orderId]);
        $order['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Fetch status history
        $stmtHistory = $pdo->prepare("
            SELECT osh.*, u.full_name as changed_by_name
            FROM `order_status_history` osh
            LEFT JOIN `users` u ON osh.changed_by_user_id = u.id
            WHERE osh.order_id = ?
            ORDER BY osh.created_at ASC
        ");
        $stmtHistory->execute([$orderId]);
        $order['history'] = $stmtHistory->fetchAll(PDO::FETCH_ASSOC);

        // Fetch payments
        $stmtPayments = $pdo->prepare("SELECT * FROM `online_payments` WHERE order_id = ? ORDER BY id DESC");
        $stmtPayments->execute([$orderId]);
        $order['payments'] = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }

    /**
     * Get orders for a specific customer with pagination
     */
    public static function forCustomer(int $customerId, int $page = 1, int $perPage = 10): array {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $stmt = $pdo->prepare("
            SELECT oo.*, COUNT(ooi.id) as total_items, d.delivery_status
            FROM `online_orders` oo
            LEFT JOIN `online_order_items` ooi ON oo.id = ooi.order_id
            LEFT JOIN `deliveries` d ON oo.id = d.order_id
            WHERE oo.customer_id = ?
            GROUP BY oo.id
            ORDER BY oo.created_at DESC, oo.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bindValue(1, $customerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $perPage, PDO::PARAM_INT);
        $stmt->bindValue(3, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count orders for a customer
     */
    public static function countForCustomer(int $customerId): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `online_orders` WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Update order status with audit log and notification
     */
    public static function updateStatus(int $orderId, string $newStatus, ?int $userId = null, string $comment = ''): bool {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT id, customer_id, order_number, status, payment_status FROM `online_orders` WHERE id = ?");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Agizo halikupatikana.");
        }

        $oldStatus = $order['status'];
        if ($oldStatus === $newStatus) {
            return true;
        }

        $validStatuses = [
            'PENDING', 'PAYMENT_PENDING', 'PAID', 'CONFIRMED', 'PROCESSING',
            'READY_FOR_DELIVERY', 'OUT_FOR_DELIVERY', 'DELIVERED', 'COMPLETED',
            'CANCELLED', 'REFUNDED'
        ];

        if (!in_array($newStatus, $validStatuses)) {
            throw new Exception("Hali ya agizo si sahihi ({$newStatus}).");
        }

        // Update timestamps if confirmed or delivered
        $extraSql = "";
        if ($newStatus === 'CONFIRMED') {
            $extraSql .= ", confirmed_at = NOW()";
        } elseif ($newStatus === 'DELIVERED' || $newStatus === 'COMPLETED') {
            $extraSql .= ", delivered_at = NOW()";
        } elseif ($newStatus === 'CANCELLED') {
            $extraSql .= ", cancelled_at = NOW(), cancelled_reason = " . $pdo->quote($comment ?: 'Kughairiwa kwa agizo');
        }

        $updateStmt = $pdo->prepare("UPDATE `online_orders` SET status = ? {$extraSql}, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$newStatus, $orderId]);

        // Insert status history
        $histStmt = $pdo->prepare("
            INSERT INTO `order_status_history` (order_id, old_status, new_status, changed_by_user_id, comment, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $histStmt->execute([$orderId, $oldStatus, $newStatus, $userId, $comment ?: "Hali imebadilishwa kuwa {$newStatus}"]);

        // Update corresponding delivery record status
        $delStatusMap = [
            'PROCESSING'         => 'PREPARING',
            'READY_FOR_DELIVERY' => 'READY',
            'OUT_FOR_DELIVERY'   => 'OUT_FOR_DELIVERY',
            'DELIVERED'          => 'DELIVERED',
            'COMPLETED'          => 'DELIVERED',
            'CANCELLED'          => 'CANCELLED'
        ];

        if (isset($delStatusMap[$newStatus])) {
            $delStatus = $delStatusMap[$newStatus];
            $pdo->prepare("UPDATE `deliveries` SET delivery_status = ?, updated_at = NOW() WHERE order_id = ?")->execute([$delStatus, $orderId]);
        }

        // Auto-integrate with sales if PAID or CONFIRMED
        if (in_array($newStatus, ['PAID', 'CONFIRMED', 'COMPLETED', 'DELIVERED'])) {
            try {
                self::integrateWithSales($orderId, $userId);
            } catch (Exception $e) {
                // Log integration warning without failing transition
            }
        }

        // Notify customer
        $statusLabels = [
            'CONFIRMED'          => 'Limethibitishwa',
            'PROCESSING'         => 'Linaandaliwa Dukani',
            'READY_FOR_DELIVERY' => 'Liko Tayari Kusafirishwa',
            'OUT_FOR_DELIVERY'   => 'Liko Njiani (Linakujia)',
            'DELIVERED'          => 'Limefikishwa Kikamilifu',
            'COMPLETED'          => 'Limekamilika',
            'CANCELLED'          => 'Limeghairiwa'
        ];

        $friendlyLabel = $statusLabels[$newStatus] ?? $newStatus;
        $pdo->prepare("
            INSERT INTO `customer_notifications` (customer_id, title, message, type, action_url, created_at)
            VALUES (?, ?, ?, 'order', ?, NOW())
        ")->execute([
            $order['customer_id'],
            "Taarifa ya Agizo #{$order['order_number']}",
            "Hali ya agizo lako imebadilika: {$friendlyLabel}.",
            "index.php?route=customer_order_show&id={$orderId}"
        ]);

        return true;
    }

    /**
     * Cancel order (handles customer or staff cancellation)
     */
    public static function cancel(int $orderId, string $reason, ?int $customerId = null): bool {
        if ($customerId !== null) {
            return self::cancelByCustomer($orderId, $customerId, $reason);
        }
        return self::updateStatus($orderId, 'CANCELLED', $reason, 1);
    }

    /**
     * Customer order cancellation with strict business rules
     * Only orders in PENDING or PAYMENT_PENDING can be cancelled by customer directly.
     */
    public static function cancelByCustomer(int $orderId, int $customerId, string $reason): bool {
        $pdo = Database::getConnection();

        $order = self::find($orderId, $customerId);
        if (!$order) {
            throw new Exception("Agizo halikupatikana au huna ruhusa nalo.");
        }

        if (!in_array($order['status'], ['PENDING', 'PAYMENT_PENDING'])) {
            throw new Exception("Agizo hili haliwezi kughairiwa moja kwa moja kwa sababu tayari limeanza kuchakatwa ({$order['status']}). Tafadhali wasiliana na huduma kwa wateja.");
        }

        $pdo->beginTransaction();
        try {
            // Restore inventory if stock was already deducted
            $items = $pdo->query("SELECT product_id, quantity, unit_cost FROM `online_order_items` WHERE order_id = {$orderId}")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($items as $it) {
                Inventory::recordMovement(
                    $it['product_id'],
                    'adjustment_in',
                    (int)$it['quantity'],
                    'online_order_cancel',
                    $orderId,
                    "Kurejesha stoo baada ya mteja kughairi agizo #{$order['order_number']}",
                    (float)$it['unit_cost'],
                    null,
                    $pdo
                );
            }

            $pdo->prepare("
                UPDATE `online_orders` 
                SET status = 'CANCELLED', cancelled_at = NOW(), cancelled_reason = ? 
                WHERE id = ?
            ")->execute([$reason ?: 'Mteja ameghairi mwenyewe', $orderId]);

            $pdo->prepare("
                INSERT INTO `order_status_history` (order_id, old_status, new_status, comment, created_at)
                VALUES (?, ?, 'CANCELLED', ?, NOW())
            ")->execute([$orderId, $order['status'], "Mteja ameghairi: " . ($reason ?: 'Bila kueleza sababu')]);

            $pdo->prepare("UPDATE `deliveries` SET delivery_status = 'CANCELLED' WHERE order_id = ?")->execute([$orderId]);

            $pdo->commit();
            return true;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
