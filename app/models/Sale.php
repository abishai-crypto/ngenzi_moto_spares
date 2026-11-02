<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Sale Model (Phase 8)
 * Handles Point of Sale (POS), Sales Management, ACID Stock Deductions,
 * Reversals, Concurrency Control & Historical Snapshot Preservation.
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Inventory;
use App\Models\Setting;
use App\Models\Payment;
use PDO;
use Exception;

require_once __DIR__ . '/Payment.php';

class Sale {

    /**
     * Generate unique sequential invoice number
     * e.g. INV-000001 or NGZ-INV-000001
     */
    public static function generateInvoiceNo(): string {
        $pdo = Database::getConnection();
        $prefix = Setting::get('invoice_prefix', 'INV-');
        if (empty($prefix)) {
            $prefix = 'INV-';
        }

        $stmt = $pdo->query("SELECT MAX(id) FROM `sales`");
        $maxId = (int)$stmt->fetchColumn();
        $nextNum = $maxId + 1;

        do {
            $candidate = sprintf("%s%06d", $prefix, $nextNum);
            $checkStmt = $pdo->prepare("SELECT id FROM `sales` WHERE invoice_no = ?");
            $checkStmt->execute([$candidate]);
            if (!$checkStmt->fetch()) {
                return $candidate;
            }
            $nextNum++;
        } while (true);
    }

    /**
     * Complete POS Sale Workflow inside an ACID Transaction
     */
    public static function createSale(array $saleData, array $cartItems, array $paymentData, int $userId): array {
        if (empty($cartItems)) {
            throw new Exception("Kapu la mauzo halina bidhaa (Cart is empty).");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Customer Verification & Snapshot
            $customerId = !empty($saleData['customer_id']) ? (int)$saleData['customer_id'] : null;
            $customerName = trim($saleData['customer_name'] ?? '');
            $customerPhone = trim($saleData['customer_phone'] ?? '');
            $mechanicId = !empty($saleData['mechanic_id']) ? (int)$saleData['mechanic_id'] : null;

            if ($customerId) {
                $cStmt = $pdo->prepare("SELECT id, customer_code, name, phone, customer_type, credit_limit, status FROM `customers` WHERE id = ?");
                $cStmt->execute([$customerId]);
                $customer = $cStmt->fetch(PDO::FETCH_ASSOC);

                if (!$customer) {
                    throw new Exception("Mteja aliyechaguliwa hajapatikana kwenye mfumo.");
                }

                if ($customer['status'] === 'blocked') {
                    throw new Exception("Mteja huyu amezuiwa (Blocked). Hawezi kufanya mauzo.");
                }

                $customerName = $customer['name'];
                $customerPhone = $customer['phone'] ?? $customerPhone;
            } else {
                if (empty($customerName)) {
                    $customerName = 'Mteja wa Kawaida (Walk-in)';
                }
            }

            // 2. Permission checks for discounts and price overrides
            $userCanSellBelowMin = Auth::hasPermission('sales.sell_below_minimum') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
            $userCanDiscount = Auth::hasPermission('sales.discount') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
            $discountThreshold = (float)Setting::get('discount_approval_threshold', '5000.00');

            // 3. Process each cart item with row locking (SELECT ... FOR UPDATE)
            $processedItems = [];
            $computedSubtotal = 0.00;
            $computedTotalDiscount = 0.00;
            $computedTotalCogs = 0.00;

            foreach ($cartItems as $index => $item) {
                $productId = (int)($item['product_id'] ?? 0);
                $qty = (int)($item['quantity'] ?? 0);
                $discountPerUnit = (float)($item['discount'] ?? 0.00);

                if ($productId <= 0) {
                    throw new Exception("Bidhaa ya " . ($index + 1) . " si sahihi.");
                }
                if ($qty <= 0) {
                    throw new Exception("Idadi ya bidhaa lazima iwe angalau 1.");
                }

                // Row lock product
                $pStmt = $pdo->prepare("
                    SELECT id, sku, barcode, name, selling_price, buying_price, 
                           minimum_selling_price, current_stock, status
                    FROM `products`
                    WHERE id = ?
                    FOR UPDATE
                ");
                $pStmt->execute([$productId]);
                $product = $pStmt->fetch(PDO::FETCH_ASSOC);

                if (!$product) {
                    throw new Exception("Kipuri chenye ID {$productId} hakikupatikana.");
                }

                if ($product['status'] !== 'active') {
                    throw new Exception("Kipuri '{$product['name']}' hakiruhusiwi kuuzwa (Status: {$product['status']}).");
                }

                // Stock availability check
                $currentStock = (int)$product['current_stock'];
                if ($qty > $currentStock) {
                    throw new Exception("Stoo haitoshi kwa ajili ya '{$product['name']}'. Kilichopo stoo ni: {$currentStock}, ulichoomba kuuza: {$qty}.");
                }

                // Price validation: use unit_price from submission or product price
                $unitPrice = isset($item['unit_price']) ? (float)$item['unit_price'] : (float)$product['selling_price'];
                if ($unitPrice <= 0) {
                    $unitPrice = (float)$product['selling_price'];
                }

                // Minimum selling price enforcement
                $minPrice = (float)($product['minimum_selling_price'] ?? 0.00);
                $effectivePrice = $unitPrice - $discountPerUnit;

                if ($minPrice > 0 && $effectivePrice < $minPrice) {
                    if (!$userCanSellBelowMin) {
                        throw new Exception("Bei ya '{$product['name']}' (TSh " . number_format($effectivePrice, 2) . ") iko chini ya bei ya chini inayoruhusiwa (TSh " . number_format($minPrice, 2) . "). Huna ruhusa ya kuuza chini ya kiwango hiki.");
                    }
                    Helper::logAudit('PRICE_OVERRIDE_APPLIED', 'POS', $productId, [
                        'standard_price' => (float)$product['selling_price'],
                        'minimum_price'  => $minPrice
                    ], [
                        'effective_price' => $effectivePrice,
                        'quantity'        => $qty,
                        'product_name'    => $product['name'],
                        'authorized_by'   => $userId
                    ]);
                }

                // Discount permission check
                if ($discountPerUnit > 0) {
                    if (!$userCanDiscount) {
                        throw new Exception("Huna ruhusa ya kutoa punguzo la bei.");
                    }
                    if (($discountPerUnit * $qty) > $discountThreshold && !Auth::hasRole('super_admin') && !Auth::hasRole('manager')) {
                        throw new Exception("Punguzo la TSh " . number_format($discountPerUnit * $qty, 2) . " linazidi kiwango kinachohitaji idhini ya Meneja (TSh " . number_format($discountThreshold, 2) . ").");
                    }
                }

                // Calculations
                $itemGrossTotal = $unitPrice * $qty;
                $itemTotalDiscount = $discountPerUnit * $qty;
                $itemSubtotal = max(0.00, $itemGrossTotal - $itemTotalDiscount);

                $unitCost = (float)$product['buying_price']; // Historical cost at time of sale!
                $itemTotalCost = $unitCost * $qty;
                $itemGrossProfit = $itemSubtotal - $itemTotalCost;
                $itemMarginPercent = ($itemSubtotal > 0) ? ($itemGrossProfit / $itemSubtotal) * 100 : 0.00;

                $computedSubtotal += $itemGrossTotal;
                $computedTotalDiscount += $itemTotalDiscount;
                $computedTotalCogs += $itemTotalCost;

                $processedItems[] = [
                    'product_id'        => $productId,
                    'product_name'      => $product['name'],
                    'product_sku'       => $product['sku'],
                    'quantity'          => $qty,
                    'unit_cost'         => $unitCost,
                    'unit_price'        => $unitPrice,
                    'discount_per_unit' => $discountPerUnit,
                    'subtotal'          => $itemSubtotal,
                    'total_cost'        => $itemTotalCost,
                    'gross_profit'      => $itemGrossProfit,
                    'gross_margin_percent' => round($itemMarginPercent, 2)
                ];
            }

            // Overall invoice discount (if applicable)
            $overallDiscount = max(0.00, (float)($saleData['discount_amount'] ?? 0.00));
            $computedTotalDiscount += $overallDiscount;
            $grandTotal = max(0.00, $computedSubtotal - $computedTotalDiscount);

            $overallGrossProfit = $grandTotal - $computedTotalCogs;
            $overallMarginPercent = ($grandTotal > 0) ? ($overallGrossProfit / $grandTotal) * 100 : 0.00;

            // 4. Payment Calculations & Methods
            $paymentMethod = trim($paymentData['payment_method'] ?? 'Cash');
            $paidAmount = isset($paymentData['amount_paid']) ? (float)$paymentData['amount_paid'] : $grandTotal;
            $referenceNo = trim($paymentData['reference_no'] ?? '');

            if ($paidAmount < 0) {
                throw new Exception("Kiasi cha malipo hakiwezi kuwa chini ya sifuri.");
            }

            // Change vs Balance Due
            $balanceDue = 0.00;
            $changeAmount = 0.00;

            if ($paidAmount >= $grandTotal) {
                $balanceDue = 0.00;
                $changeAmount = $paidAmount - $grandTotal;
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $balanceDue = $grandTotal - $paidAmount;
                $changeAmount = 0.00;
                $paymentStatus = 'partially_paid';
            } else {
                $balanceDue = $grandTotal;
                $changeAmount = 0.00;
                $paymentStatus = ($paymentMethod === 'Credit') ? 'credit' : 'unpaid';
            }

            // Credit Sale Validations & Eligibility (Phase 9)
            $userCanOverrideCredit = Auth::hasPermission('credit.override_limit') || Auth::hasRole('super_admin') || Auth::hasRole('manager');
            $creditOverrideBy = null;
            $creditOverrideReason = null;
            $dueDate = null;
            $paymentTerms = 'Cash only';

            if ($balanceDue > 0) {
                if (!$customerId) {
                    throw new Exception("Mauzo ya mkopo au nusu-malipo yanahitaji mteja aliyesajiliwa (Walk-in hawezi kupewa mkopo).");
                }

                // Eligibility check via Customer model
                $eligibility = Customer::checkCreditEligibility($customerId, $balanceDue, $userCanOverrideCredit);

                if (!$eligibility['allowed']) {
                    throw new Exception($eligibility['reason']);
                }

                if (!empty($eligibility['needs_override']) && !empty($eligibility['is_over_limit'])) {
                    // Override is approved by authorized user
                    $creditOverrideBy = !empty($saleData['credit_override_by']) ? (int)$saleData['credit_override_by'] : $userId;
                    $creditOverrideReason = trim($saleData['credit_override_reason'] ?? $eligibility['reason']);
                }

                // Calculate Due Date based on payment terms
                $paymentTerms = $saleData['payment_terms'] ?? ($eligibility['payment_terms'] ?? '30 days');
                if (!empty($saleData['due_date'])) {
                    $dueDate = $saleData['due_date'];
                } else {
                    $termDays = 30;
                    if (preg_match('/(\d+)/', $paymentTerms, $m)) {
                        $termDays = (int)$m[1];
                    }
                    $dueDate = date('Y-m-d', strtotime("+{$termDays} days"));
                }
            }

            // 5. Generate Invoice Number & Insert `sales`
            $invoiceNo = self::generateInvoiceNo();
            $discountReason = trim($saleData['discount_reason'] ?? '');
            $notes = trim($saleData['notes'] ?? '');

            $stmtSale = $pdo->prepare("
                INSERT INTO `sales` (
                    invoice_no, customer_id, customer_name, customer_phone, mechanic_id,
                    subtotal, discount_amount, discount_reason, discount_approved_by,
                    total_amount, total_cogs, gross_profit, gross_margin_percent,
                    paid_amount, balance_due, payment_method, payment_status, sale_status,
                    notes, credit_override_by, credit_override_reason, cashier_id,
                    sale_date, due_date, payment_terms, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, 'completed',
                    ?, ?, ?, ?,
                    NOW(), ?, ?, NOW()
                )
            ");

            $stmtSale->execute([
                $invoiceNo,
                $customerId,
                $customerName,
                $customerPhone,
                $mechanicId,
                $computedSubtotal,
                $computedTotalDiscount,
                $discountReason ?: null,
                ($overallDiscount > $discountThreshold) ? $userId : null,
                $grandTotal,
                $computedTotalCogs,
                $overallGrossProfit,
                round($overallMarginPercent, 2),
                min($paidAmount, $grandTotal),
                $balanceDue,
                $paymentMethod,
                $paymentStatus,
                $notes ?: null,
                $creditOverrideBy,
                $creditOverrideReason,
                $userId,
                $dueDate,
                $paymentTerms
            ]);

            $saleId = (int)$pdo->lastInsertId();

            // Log credit override in audit trail if applicable
            if ($creditOverrideBy && $creditOverrideReason) {
                Helper::logAudit('CREDIT_OVERRIDE', 'credit', $customerId, null, [
                    'sale_id'          => $saleId,
                    'invoice_no'       => $invoiceNo,
                    'credit_amount'    => $balanceDue,
                    'approved_by'      => $creditOverrideBy,
                    'reason'           => $creditOverrideReason
                ]);
            }

            // 6. Insert `sale_items` and Deduct Stock via `Inventory::recordMovement`
            $stmtItem = $pdo->prepare("
                INSERT INTO `sale_items` (
                    sale_id, product_id, product_name, product_sku, quantity,
                    unit_cost, unit_price, discount_per_unit, subtotal,
                    total_cost, gross_profit, gross_margin_percent, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, NOW()
                )
            ");

            foreach ($processedItems as $pItem) {
                $stmtItem->execute([
                    $saleId,
                    $pItem['product_id'],
                    $pItem['product_name'],
                    $pItem['product_sku'],
                    $pItem['quantity'],
                    $pItem['unit_cost'],
                    $pItem['unit_price'],
                    $pItem['discount_per_unit'],
                    $pItem['subtotal'],
                    $pItem['total_cost'],
                    $pItem['gross_profit'],
                    $pItem['gross_margin_percent']
                ]);

                // Inventory transaction & stock deduction
                Inventory::recordMovement(
                    $pItem['product_id'],
                    'sale',
                    -$pItem['quantity'],
                    'sale',
                    $saleId,
                    "Mauzo ya dukani - Ankara #{$invoiceNo}",
                    $pItem['unit_cost'],
                    $userId,
                    $pdo
                );
            }

            // 7. Insert `sale_payments` with unique payment_no (Phase 9)
            $paymentNo = null;
            if ($paidAmount > 0) {
                $effectivePaidForThisSale = min($paidAmount, $grandTotal);
                $paymentNo = Payment::generatePaymentNo($pdo);
                $provider = !empty($paymentData['provider']) ? trim($paymentData['provider']) : null;

                $stmtPayment = $pdo->prepare("
                    INSERT INTO `sale_payments` (
                        payment_no, sale_id, customer_id, amount, payment_method,
                        provider, reference_no, payment_date, received_by, notes, status, created_at
                    ) VALUES (
                        ?, ?, ?, ?, ?,
                        ?, ?, NOW(), ?, ?, 'completed', NOW()
                    )
                ");
                $stmtPayment->execute([
                    $paymentNo,
                    $saleId,
                    $customerId,
                    $effectivePaidForThisSale,
                    $paymentMethod,
                    $provider,
                    $referenceNo ?: null,
                    $userId,
                    $changeAmount > 0 ? "Pesa iliyopokelewa: TSh " . number_format($paidAmount, 2) . " (Chenji: TSh " . number_format($changeAmount, 2) . ")" : null
                ]);
            }

            // 8. Update customer current_balance if credit/partial sale
            if ($customerId && $balanceDue > 0) {
                $custUpd = $pdo->prepare("
                    UPDATE `customers`
                    SET current_balance = current_balance + ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $custUpd->execute([$balanceDue, $customerId]);
            }

            // 9. Audit Trail
            Helper::logAudit(
                'SALE_CREATED',
                'sales',
                $saleId,
                null,
                [
                    'invoice_no'     => $invoiceNo,
                    'customer'       => $customerName,
                    'total_amount'   => $grandTotal,
                    'paid_amount'    => $paidAmount,
                    'balance_due'    => $balanceDue,
                    'due_date'       => $dueDate,
                    'payment_terms'  => $paymentTerms,
                    'payment_no'     => $paymentNo,
                    'items_count'    => count($processedItems),
                    'payment_method' => $paymentMethod
                ]
            );

            $pdo->commit();

            return [
                'sale_id'        => $saleId,
                'invoice_no'     => $invoiceNo,
                'customer_name'  => $customerName,
                'total_amount'   => $grandTotal,
                'paid_amount'    => $paidAmount,
                'change_amount'  => $changeAmount,
                'balance_due'    => $balanceDue,
                'due_date'       => $dueDate,
                'payment_terms'  => $paymentTerms,
                'payment_no'     => $paymentNo,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod,
                'items_count'    => count($processedItems)
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancel a completed sale and reverse stock movements
     */
    public static function cancelSale(int $saleId, string $reason, int $userId): bool {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Lock sale
            $stmt = $pdo->prepare("SELECT * FROM `sales` WHERE id = ? FOR UPDATE");
            $stmt->execute([$saleId]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$sale) {
                throw new Exception("Mauzo hayajapatikana.");
            }

            if ($sale['sale_status'] === 'cancelled') {
                throw new Exception("Mauzo haya tayari yamekwishafutwa (Already cancelled).");
            }

            // Fetch items
            $itemsStmt = $pdo->prepare("SELECT * FROM `sale_items` WHERE sale_id = ?");
            $itemsStmt->execute([$saleId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            // Reverse inventory for each item
            foreach ($items as $item) {
                Inventory::recordMovement(
                    (int)$item['product_id'],
                    'sale_return',
                    +(int)$item['quantity'],
                    'sale_cancellation',
                    $saleId,
                    "Kughairi mauzo ya ankara #{$sale['invoice_no']}. Sababu: {$reason}",
                    (float)$item['unit_cost'],
                    $userId,
                    $pdo
                );
            }

            // Update sale status
            $upStmt = $pdo->prepare("
                UPDATE `sales` 
                SET sale_status = 'cancelled', 
                    notes = CONCAT(COALESCE(notes, ''), ' | GHAFALA/KUGHAIRI: ', ?),
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $upStmt->execute([$reason, $saleId]);

            // Audit log
            Helper::logAudit(
                'SALE_CANCELLED',
                'sales',
                $saleId,
                ['status' => $sale['sale_status']],
                ['status' => 'cancelled', 'reason' => $reason, 'invoice_no' => $sale['invoice_no']]
            );

            $pdo->commit();
            return true;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Find sale by ID
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   u.full_name as cashier_name, u.username as cashier_username,
                   c.customer_code, c.email as customer_email, c.address as customer_address,
                   c.area as customer_area,
                   m.name as mechanic_name, m.phone as mechanic_phone
            FROM `sales` s
            LEFT JOIN `users` u ON s.cashier_id = u.id
            LEFT JOIN `customers` c ON s.customer_id = c.id
            LEFT JOIN `mechanics` m ON s.mechanic_id = m.id
            WHERE s.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find sale by Invoice Number
     */
    public static function findByInvoice(string $invoiceNo): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   u.full_name as cashier_name,
                   c.customer_code,
                   m.name as mechanic_name
            FROM `sales` s
            LEFT JOIN `users` u ON s.cashier_id = u.id
            LEFT JOIN `customers` c ON s.customer_id = c.id
            LEFT JOIN `mechanics` m ON s.mechanic_id = m.id
            WHERE s.invoice_no = ?
            LIMIT 1
        ");
        $stmt->execute([trim($invoiceNo)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get items of a sale
     */
    public static function getItems(int $saleId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT si.*, 
                   p.name as live_product_name, p.sku as live_product_sku,
                   p.shelf_location,
                   u.short_name as unit_name,
                   b.name as brand_name,
                   cat.name as category_name
            FROM `sale_items` si
            LEFT JOIN `products` p ON si.product_id = p.id
            LEFT JOIN `units` u ON p.unit_id = u.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            LEFT JOIN `categories` cat ON p.category_id = cat.id
            WHERE si.sale_id = ?
            ORDER BY si.id ASC
        ");
        $stmt->execute([$saleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get payments of a sale
     */
    public static function getPayments(int $saleId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT sp.*, u.full_name as received_by_name
            FROM `sale_payments` sp
            LEFT JOIN `users` u ON sp.received_by = u.id
            WHERE sp.sale_id = ?
            ORDER BY sp.id ASC
        ");
        $stmt->execute([$saleId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * List sales with pagination and filters
     */
    public static function all(array $filters = [], int $page = 1, int $perPage = 20): array {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT s.*, 
                   u.full_name as cashier_name,
                   c.customer_code,
                   (SELECT COUNT(*) FROM `sale_items` WHERE sale_id = s.id) as items_count
            FROM `sales` s
            LEFT JOIN `users` u ON s.cashier_id = u.id
            LEFT JOIN `customers` c ON s.customer_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%" . trim($filters['search']) . "%";
            $sql .= " AND (s.invoice_no LIKE ? OR s.customer_name LIKE ? OR s.customer_phone LIKE ? OR c.customer_code LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term]);
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND s.customer_id = ?";
            $params[] = (int)$filters['customer_id'];
        }

        if (!empty($filters['cashier_id'])) {
            $sql .= " AND s.cashier_id = ?";
            $params[] = (int)$filters['cashier_id'];
        }

        if (!empty($filters['payment_status'])) {
            $sql .= " AND s.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['sale_status'])) {
            $sql .= " AND s.sale_status = ?";
            $params[] = $filters['sale_status'];
        }

        if (!empty($filters['payment_method'])) {
            $sql .= " AND s.payment_method = ?";
            $params[] = $filters['payment_method'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(s.sale_date) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(s.sale_date) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY s.sale_date DESC LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count sales matching filters
     */
    public static function count(array $filters = []): int {
        $pdo = Database::getConnection();

        $sql = "
            SELECT COUNT(s.id)
            FROM `sales` s
            LEFT JOIN `customers` c ON s.customer_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%" . trim($filters['search']) . "%";
            $sql .= " AND (s.invoice_no LIKE ? OR s.customer_name LIKE ? OR s.customer_phone LIKE ? OR c.customer_code LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term]);
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND s.customer_id = ?";
            $params[] = (int)$filters['customer_id'];
        }

        if (!empty($filters['cashier_id'])) {
            $sql .= " AND s.cashier_id = ?";
            $params[] = (int)$filters['cashier_id'];
        }

        if (!empty($filters['payment_status'])) {
            $sql .= " AND s.payment_status = ?";
            $params[] = $filters['payment_status'];
        }

        if (!empty($filters['sale_status'])) {
            $sql .= " AND s.sale_status = ?";
            $params[] = $filters['sale_status'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(s.sale_date) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(s.sale_date) <= ?";
            $params[] = $filters['date_to'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get Daily Summary Statistics
     */
    public static function getDailySummary(string $date): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT 
                COUNT(id) as total_sales_count,
                COALESCE(SUM(CASE WHEN sale_status != 'cancelled' THEN total_amount ELSE 0 END), 0.00) as total_revenue,
                COALESCE(SUM(CASE WHEN sale_status != 'cancelled' THEN paid_amount ELSE 0 END), 0.00) as total_paid,
                COALESCE(SUM(CASE WHEN sale_status != 'cancelled' THEN balance_due ELSE 0 END), 0.00) as total_balance_due,
                COALESCE(SUM(CASE WHEN sale_status != 'cancelled' THEN discount_amount ELSE 0 END), 0.00) as total_discounts,
                COALESCE(SUM(CASE WHEN sale_status != 'cancelled' THEN total_cogs ELSE 0 END), 0.00) as total_cogs,
                COALESCE(SUM(CASE WHEN sale_status != 'cancelled' THEN gross_profit ELSE 0 END), 0.00) as total_gross_profit,
                COUNT(CASE WHEN sale_status = 'cancelled' THEN 1 END) as cancelled_count,
                COUNT(CASE WHEN payment_status = 'credit' OR (balance_due > 0 AND sale_status != 'cancelled') THEN 1 END) as credit_sales_count
            FROM `sales`
            WHERE DATE(sale_date) = ?
        ");
        $stmt->execute([$date]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculate margin
        $revenue = (float)$res['total_revenue'];
        $profit = (float)$res['total_gross_profit'];
        $res['margin_percent'] = ($revenue > 0) ? round(($profit / $revenue) * 100, 2) : 0.00;

        return $res;
    }

    /**
     * Cashier Daily Breakdown by payment method
     */
    public static function getCashierDailySummary(string $date, ?int $cashierId = null): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                u.id as cashier_id,
                u.full_name as cashier_name,
                COUNT(s.id) as total_transactions,
                COALESCE(SUM(CASE WHEN s.sale_status != 'cancelled' THEN s.total_amount ELSE 0 END), 0.00) as total_sales,
                COALESCE(SUM(CASE WHEN s.sale_status != 'cancelled' AND s.payment_method = 'Cash' THEN s.paid_amount ELSE 0 END), 0.00) as cash_sales,
                COALESCE(SUM(CASE WHEN s.sale_status != 'cancelled' AND s.payment_method IN ('M-Pesa', 'Tigo Pesa', 'Airtel Money', 'Halopesa', 'Mobile Money') THEN s.paid_amount ELSE 0 END), 0.00) as mobile_money_sales,
                COALESCE(SUM(CASE WHEN s.sale_status != 'cancelled' AND s.payment_method IN ('Bank', 'Card') THEN s.paid_amount ELSE 0 END), 0.00) as bank_card_sales,
                COALESCE(SUM(CASE WHEN s.sale_status != 'cancelled' AND (s.payment_method = 'Credit' OR s.balance_due > 0) THEN s.balance_due ELSE 0 END), 0.00) as credit_sales,
                COUNT(CASE WHEN s.sale_status = 'cancelled' THEN 1 END) as cancelled_transactions
            FROM `users` u
            LEFT JOIN `sales` s ON s.cashier_id = u.id AND DATE(s.sale_date) = ?
            WHERE u.role_id IN (SELECT id FROM `roles` WHERE name IN ('cashier', 'manager', 'super_admin'))
        ";
        $params = [$date];

        if ($cashierId) {
            $sql .= " AND u.id = ?";
            $params[] = $cashierId;
        }

        $sql .= " GROUP BY u.id, u.full_name ORDER BY total_sales DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Product sales stats (units sold, total revenue, last sale date)
     */
    public static function getProductSalesStats(int $productId): array {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("
            SELECT 
                COALESCE(SUM(si.quantity), 0) as total_units_sold,
                COALESCE(SUM(si.subtotal), 0.00) as total_revenue,
                MAX(s.sale_date) as last_sale_date,
                COUNT(DISTINCT s.id) as total_sales_count
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            WHERE si.product_id = ? AND s.sale_status != 'cancelled'
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
