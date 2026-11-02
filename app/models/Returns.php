<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Returns Model (Phase 11)
 * Handles Returns & Refunds Management, Idempotent Restocking,
 * Financial Impact Adjustments, Concurrency Protection & Audit Logs.
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Inventory;
use App\Models\Setting;
use App\Models\Payment;
use App\Models\Customer;
use PDO;
use Exception;

require_once __DIR__ . '/Payment.php';
require_once __DIR__ . '/Inventory.php';
require_once __DIR__ . '/Customer.php';

class Returns {

    /**
     * Generate unique sequential return number (e.g. RET-000001)
     * Concurrency-safe with database check
     */
    public static function generateReturnNumber(?PDO $pdo = null): string {
        $pdo = $pdo ?? Database::getConnection();
        $prefix = Setting::get('return_prefix', 'RET-');
        if (empty($prefix)) {
            $prefix = 'RET-';
        }

        $stmt = $pdo->query("SELECT MAX(id) FROM `returns`");
        $maxId = (int)$stmt->fetchColumn();
        $nextNum = $maxId + 1;

        do {
            $candidate = sprintf("%s%06d", $prefix, $nextNum);
            $checkStmt = $pdo->prepare("SELECT id FROM `returns` WHERE return_number = ?");
            $checkStmt->execute([$candidate]);
            if (!$checkStmt->fetch()) {
                return $candidate;
            }
            $nextNum++;
        } while (true);
    }

    /**
     * Generate unique sequential refund payment number (e.g. REF-000001)
     */
    public static function generateRefundNumber(?PDO $pdo = null): string {
        $pdo = $pdo ?? Database::getConnection();
        $prefix = Setting::get('refund_receipt_prefix', 'REF-');
        if (empty($prefix)) {
            $prefix = 'REF-';
        }

        $stmt = $pdo->query("SELECT COUNT(*) FROM `returns` WHERE refund_reference_no LIKE '{$prefix}%'");
        $count = (int)$stmt->fetchColumn();
        $nextNum = $count + 1;

        do {
            $candidate = sprintf("%s%06d", $prefix, $nextNum);
            $checkStmt = $pdo->prepare("SELECT id FROM `returns` WHERE refund_reference_no = ?");
            $checkStmt->execute([$candidate]);
            if (!$checkStmt->fetch()) {
                return $candidate;
            }
            $nextNum++;
        } while (true);
    }

    /**
     * Get items of a sale with remaining returnable quantity calculation
     * Returns array of sale items with `original_quantity`, `already_returned_quantity`, and `remaining_returnable_quantity`.
     */
    public static function getReturnableItemsForSale(int $saleId, ?int $excludeReturnId = null): array {
        $pdo = Database::getConnection();

        // 1. Fetch sale items
        $stmt = $pdo->prepare("
            SELECT si.*, 
                   p.name as live_product_name, p.sku as live_product_sku, p.current_stock,
                   u.short_name as unit_name, b.name as brand_name, cat.name as category_name
            FROM `sale_items` si
            LEFT JOIN `products` p ON si.product_id = p.id
            LEFT JOIN `units` u ON p.unit_id = u.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            LEFT JOIN `categories` cat ON p.category_id = cat.id
            WHERE si.sale_id = ?
            ORDER BY si.id ASC
        ");
        $stmt->execute([$saleId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Calculate already returned quantities from active returns (excluding cancelled/rejected)
        $sqlRet = "
            SELECT ri.sale_item_id, COALESCE(SUM(ri.quantity), 0) as total_returned
            FROM `return_items` ri
            JOIN `returns` r ON ri.return_id = r.id
            WHERE r.sale_id = ? AND r.status NOT IN ('cancelled', 'rejected')
        ";
        $paramsRet = [$saleId];
        if ($excludeReturnId) {
            $sqlRet .= " AND r.id != ?";
            $paramsRet[] = $excludeReturnId;
        }
        $sqlRet .= " GROUP BY ri.sale_item_id";

        $stmtRet = $pdo->prepare($sqlRet);
        $stmtRet->execute($paramsRet);
        $returnedMap = $stmtRet->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($items as &$it) {
            $saleItemId = (int)$it['id'];
            $origQty = (int)$it['quantity'];
            $alreadyReturned = (int)($returnedMap[$saleItemId] ?? 0);
            $remaining = max(0, $origQty - $alreadyReturned);

            $it['original_quantity'] = $origQty;
            $it['already_returned'] = $alreadyReturned;
            $it['remaining_returnable'] = $remaining;

            // Net unit selling price (unit_price - discount_per_unit)
            $unitPrice = (float)$it['unit_price'];
            $unitDiscount = (float)($it['discount_per_unit'] ?? 0.00);
            $it['net_unit_price'] = max(0.00, $unitPrice - $unitDiscount);
        }

        return $items;
    }

    /**
     * Create Return Workflow inside an ACID Transaction
     */
    public static function createReturn(array $returnData, array $itemsData, int $userId): array {
        if (empty($itemsData)) {
            throw new Exception("Hujachagua kipuri chochote cha kurejesha (No return items selected).");
        }

        $saleId = (int)($returnData['sale_id'] ?? 0);
        if ($saleId <= 0) {
            throw new Exception("Nambari ya ankara ya mauzo haijatolewa.");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Lock sale record for update
            $sStmt = $pdo->prepare("SELECT * FROM `sales` WHERE id = ? FOR UPDATE");
            $sStmt->execute([$saleId]);
            $sale = $sStmt->fetch(PDO::FETCH_ASSOC);

            if (!$sale) {
                throw new Exception("Ankara ya mauzo haikupatikana.");
            }

            if ($sale['sale_status'] === 'cancelled') {
                throw new Exception("Huwezi kurejesha bidhaa kutoka kwenye mauzo yaliyoghairiwa (Cancelled Sale).");
            }

            // 2. Fetch returnable limits with lock
            $returnableItems = self::getReturnableItemsForSale($saleId);
            $returnableMap = [];
            foreach ($returnableItems as $rit) {
                $returnableMap[(int)$rit['id']] = $rit;
            }

            $processedItems = [];
            $computedGrossSubtotal = 0.00;
            $computedTotalCogs = 0.00;

            foreach ($itemsData as $idx => $it) {
                $saleItemId = (int)($it['sale_item_id'] ?? 0);
                $returnQty = (int)($it['quantity'] ?? 0);

                if ($returnQty <= 0) {
                    continue; // Skip zero quantity lines
                }

                if (!isset($returnableMap[$saleItemId])) {
                    throw new Exception("Kipuri kwenye mstari wa " . ($idx + 1) . " hakipo kwenye ankara hii.");
                }

                $origItem = $returnableMap[$saleItemId];
                $maxReturnable = (int)$origItem['remaining_returnable'];

                if ($returnQty > $maxReturnable) {
                    throw new Exception(
                        "Idadi inayorejeshwa ({$returnQty}) kwa ajili ya '{$origItem['product_name']}' inazidi idadi inayoruhusiwa kurejeshwa ({$maxReturnable}). " .
                        "Iliyouzwa: {$origItem['original_quantity']}, Iliyokwisharejeshwa: {$origItem['already_returned']}."
                    );
                }

                // Snapshots
                $productId = (int)$origItem['product_id'];
                $productName = $origItem['product_name'] ?: ($origItem['live_product_name'] ?? 'Kipuri');
                $productSku = $origItem['product_sku'] ?: ($origItem['live_product_sku'] ?? '');
                $unitSellingPrice = (float)$origItem['unit_price'];
                $unitDiscount = (float)($origItem['discount_per_unit'] ?? 0.00);
                $unitRefundPrice = max(0.00, $unitSellingPrice - $unitDiscount);
                $unitCost = (float)$origItem['unit_cost']; // Historical cost at sale time!

                $itemSubtotal = $unitRefundPrice * $returnQty;
                $itemTotalCogs = $unitCost * $returnQty;

                $condition = trim($it['condition_status'] ?? 'resellable');
                if (!in_array($condition, ['resellable', 'damaged', 'defective', 'not_resellable'])) {
                    $condition = 'resellable';
                }

                $itemReason = !empty($it['return_reason']) ? trim($it['return_reason']) : trim($returnData['reason'] ?? 'CUSTOMER_RETURN');
                $itemNotes = !empty($it['notes']) ? trim($it['notes']) : null;
                $restockable = ($condition === 'resellable') ? 1 : 0;

                $computedGrossSubtotal += $itemSubtotal;
                $computedTotalCogs += $itemTotalCogs;

                $processedItems[] = [
                    'sale_item_id'        => $saleItemId,
                    'product_id'          => $productId,
                    'product_name'        => $productName,
                    'product_sku'         => $productSku,
                    'original_quantity'   => (int)$origItem['quantity'],
                    'quantity'            => $returnQty,
                    'unit_selling_price'  => $unitSellingPrice,
                    'unit_discount'       => $unitDiscount,
                    'unit_refund_price'   => $unitRefundPrice,
                    'unit_cost'           => $unitCost,
                    'subtotal'            => $itemSubtotal,
                    'total_cogs'          => $itemTotalCogs,
                    'condition_status'    => $condition,
                    'return_reason'       => $itemReason,
                    'restockable'         => $restockable,
                    'notes'               => $itemNotes
                ];
            }

            if (empty($processedItems)) {
                throw new Exception("Weka angalau idadi 1 ya kipuri kimoja unachotaka kurejesha.");
            }

            // 3. Return Fees & Net Refund Amount
            $returnFee = max(0.00, (float)($returnData['return_fee'] ?? 0.00));
            $netRefundAmount = max(0.00, $computedGrossSubtotal - $returnFee);

            // 4. Determine initial status & permissions
            $initialStatus = 'requested';
            $autoApprove = !empty($returnData['auto_approve']) && (Auth::hasRole(['super_admin', 'manager']) || Auth::hasPermission('returns.approve'));
            if ($autoApprove) {
                $initialStatus = 'approved';
            } elseif (!empty($returnData['is_draft'])) {
                $initialStatus = 'draft';
            }

            $returnNumber = self::generateReturnNumber($pdo);
            $customerId = !empty($sale['customer_id']) ? (int)$sale['customer_id'] : null;
            $returnType = trim($returnData['return_type'] ?? 'CUSTOMER_RETURN');
            $reason = trim($returnData['reason'] ?? 'CUSTOMER_RETURN');
            $notes = trim($returnData['notes'] ?? '');
            $returnDate = !empty($returnData['return_date']) ? $returnData['return_date'] : date('Y-m-d');

            // 5. Insert `returns` record
            $insRetStmt = $pdo->prepare("
                INSERT INTO `returns` (
                    return_number, sale_id, customer_id, return_type, return_date,
                    reason, subtotal, return_fee, refund_amount, status, notes,
                    created_by, approved_by, approved_at, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, NOW()
                )
            ");
            $insRetStmt->execute([
                $returnNumber,
                $saleId,
                $customerId,
                $returnType,
                $returnDate,
                $reason,
                $computedGrossSubtotal,
                $returnFee,
                $netRefundAmount,
                $initialStatus,
                $notes ?: null,
                $userId,
                $autoApprove ? $userId : null,
                $autoApprove ? date('Y-m-d H:i:s') : null
            ]);

            $returnId = (int)$pdo->lastInsertId();

            // 6. Insert `return_items`
            $insItemStmt = $pdo->prepare("
                INSERT INTO `return_items` (
                    return_id, sale_item_id, product_id, product_name, product_sku,
                    original_quantity, quantity, unit_selling_price, unit_discount,
                    unit_refund_price, unit_cost, subtotal, total_cogs, condition_status,
                    return_reason, restockable, is_restocked, notes, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, 0, ?, NOW()
                )
            ");

            foreach ($processedItems as $pItem) {
                $insItemStmt->execute([
                    $returnId,
                    $pItem['sale_item_id'],
                    $pItem['product_id'],
                    $pItem['product_name'],
                    $pItem['product_sku'],
                    $pItem['original_quantity'],
                    $pItem['quantity'],
                    $pItem['unit_selling_price'],
                    $pItem['unit_discount'],
                    $pItem['unit_refund_price'],
                    $pItem['unit_cost'],
                    $pItem['subtotal'],
                    $pItem['total_cogs'],
                    $pItem['condition_status'],
                    $pItem['return_reason'],
                    $pItem['restockable'],
                    $pItem['notes']
                ]);
            }

            // 7. Audit Log
            Helper::logAudit(
                'RETURN_CREATED',
                'returns',
                $returnId,
                null,
                [
                    'return_number'    => $returnNumber,
                    'invoice_no'       => $sale['invoice_no'],
                    'customer_id'      => $customerId,
                    'status'           => $initialStatus,
                    'subtotal'         => $computedGrossSubtotal,
                    'return_fee'       => $returnFee,
                    'refund_amount'    => $netRefundAmount,
                    'items_count'      => count($processedItems)
                ]
            );

            $pdo->commit();

            return [
                'return_id'        => $returnId,
                'return_number'    => $returnNumber,
                'sale_id'          => $saleId,
                'invoice_no'       => $sale['invoice_no'],
                'status'           => $initialStatus,
                'subtotal'         => $computedGrossSubtotal,
                'return_fee'       => $returnFee,
                'refund_amount'    => $netRefundAmount,
                'items_count'      => count($processedItems)
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Approve Return (Transition from requested/draft -> approved)
     */
    public static function approveReturn(int $returnId, int $userId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("SELECT * FROM `returns` WHERE id = ? FOR UPDATE");
            $stmt->execute([$returnId]);
            $return = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$return) {
                throw new Exception("Rejesho halikupatikana.");
            }

            if (!in_array($return['status'], ['draft', 'requested'])) {
                throw new Exception("Huwezi kuidhinisha rejesho lenye hadhi ya: {$return['status']}.");
            }

            $uStmt = $pdo->prepare("
                UPDATE `returns`
                SET status = 'approved', approved_by = ?, approved_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $uStmt->execute([$userId, $returnId]);

            Helper::logAudit('RETURN_APPROVED', 'returns', $returnId, ['status' => $return['status']], ['status' => 'approved']);

            $pdo->commit();
            return ['success' => true, 'status' => 'approved'];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Receive returned goods and restock resellable items into inventory
     * Idempotency protected: Prevents double restocking!
     */
    public static function receiveAndRestock(int $returnId, int $userId, ?string $notes = null): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Lock return record
            $stmt = $pdo->prepare("SELECT * FROM `returns` WHERE id = ? FOR UPDATE");
            $stmt->execute([$returnId]);
            $return = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$return) {
                throw new Exception("Rejesho halikupatikana.");
            }

            if (in_array($return['status'], ['rejected', 'cancelled'])) {
                throw new Exception("Huwezi kupokea rejesho lililokataliwa au kughairiwa.");
            }

            // Check if already restocked (Double-restocking protection)
            if ((int)$return['is_restocked'] === 1) {
                throw new Exception("Vipuri vya rejesho hili (#{$return['return_number']}) tayari vimeshapokelewa na kuingizwa stoo (Already Restocked).");
            }

            // Fetch return items
            $itemsStmt = $pdo->prepare("SELECT * FROM `return_items` WHERE return_id = ? FOR UPDATE");
            $itemsStmt->execute([$returnId]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

            $restockedCount = 0;
            $damagedCount = 0;

            foreach ($items as $it) {
                $riId = (int)$it['id'];
                $productId = (int)$it['product_id'];
                $qty = (int)$it['quantity'];
                $unitCost = (float)$it['unit_cost'];
                $condition = $it['condition_status'];

                if ((int)$it['is_restocked'] === 1) {
                    continue; // Skip if already individually marked
                }

                if ($condition === 'resellable') {
                    // Restock normal sellable stock
                    Inventory::recordMovement(
                        $productId,
                        'sale_return',
                        +$qty,
                        'return',
                        $returnId,
                        "Marejesho ya mteja - Rejesho #{$return['return_number']}",
                        $unitCost,
                        $userId,
                        $pdo
                    );

                    $upItem = $pdo->prepare("UPDATE `return_items` SET is_restocked = 1, restocked_at = NOW() WHERE id = ?");
                    $upItem->execute([$riId]);
                    $restockedCount++;
                } else {
                    // Damaged / Defective / Not Resellable: DO NOT increase normal sellable stock!
                    // Mark as processed without increasing inventory
                    $upItem = $pdo->prepare("UPDATE `return_items` SET is_restocked = 0, restocked_at = NOW(), notes = CONCAT(COALESCE(notes, ''), ' | Imepokelewa: ', ?) WHERE id = ?");
                    $upItem->execute(["Haikuingizwa stoo kwa sababu ya hali ya bidhaa ({$condition})", $riId]);
                    $damagedCount++;
                }
            }

            // Update return status
            $newStatus = ($return['status'] === 'refunded') ? 'completed' : 'received';
            $uRet = $pdo->prepare("
                UPDATE `returns`
                SET status = ?, is_restocked = 1, received_by = ?, received_at = NOW(),
                    notes = CASE WHEN ? IS NOT NULL THEN CONCAT(COALESCE(notes, ''), ' | ', ?) ELSE notes END,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $uRet->execute([$newStatus, $userId, $notes, $notes, $returnId]);

            Helper::logAudit(
                'RETURN_RECEIVED_RESTOCKED',
                'returns',
                $returnId,
                ['status' => $return['status'], 'is_restocked' => 0],
                [
                    'status'          => $newStatus,
                    'is_restocked'    => 1,
                    'restocked_lines' => $restockedCount,
                    'damaged_lines'   => $damagedCount
                ]
            );

            $pdo->commit();

            return [
                'success'         => true,
                'status'          => $newStatus,
                'restocked_lines' => $restockedCount,
                'damaged_lines'   => $damagedCount
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Process Refund for Return (Cash, Original Payment, Customer Credit, Debt Reduction)
     * Full financial integrity & credit sale handling
     */
    public static function processRefund(int $returnId, array $refundData, int $userId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Lock return record
            $rStmt = $pdo->prepare("SELECT * FROM `returns` WHERE id = ? FOR UPDATE");
            $rStmt->execute([$returnId]);
            $return = $rStmt->fetch(PDO::FETCH_ASSOC);

            if (!$return) {
                throw new Exception("Rejesho halikupatikana.");
            }

            if (in_array($return['status'], ['rejected', 'cancelled'])) {
                throw new Exception("Huwezi kutoa marejesho ya fedha kwa rejesho lililokataliwa au kughairiwa.");
            }

            if ($return['refund_status'] === 'refunded' || $return['refund_status'] === 'debt_reduced' || !empty($return['refund_processed_at']) || in_array($return['status'], ['refunded', 'completed'])) {
                throw new Exception("Marejesho ya fedha kwa rejesho hili (#{$return['return_number']}) tayari yameshafanyika (Already Refunded).");
            }

            // Lock sale record
            $saleId = (int)$return['sale_id'];
            $sStmt = $pdo->prepare("SELECT * FROM `sales` WHERE id = ? FOR UPDATE");
            $sStmt->execute([$saleId]);
            $sale = $sStmt->fetch(PDO::FETCH_ASSOC);

            if (!$sale) {
                throw new Exception("Ankara ya mauzo haikupatikana.");
            }

            $refundAmount = (float)$return['refund_amount'];
            $saleBalanceDue = (float)$sale['balance_due'];
            $customerId = !empty($return['customer_id']) ? (int)$return['customer_id'] : (!empty($sale['customer_id']) ? (int)$sale['customer_id'] : null);

            $chosenMethod = trim($refundData['refund_method'] ?? 'Cash');
            $provider = !empty($refundData['provider']) ? trim($refundData['provider']) : null;
            $refundRefNo = self::generateRefundNumber($pdo);

            $debtReducedAmount = 0.00;
            $cashRefundAmount = 0.00;
            $customerCreditAmount = 0.00;
            $refundStatus = 'refunded';

            // CRITICAL CREDIT SALE HANDLING:
            // If the original sale was a credit sale or has unpaid balance, reduce invoice balance due & customer debt first!
            if ($saleBalanceDue > 0) {
                if ($refundAmount <= $saleBalanceDue) {
                    // Entire return amount goes towards reducing debt on this invoice
                    $debtReducedAmount = $refundAmount;
                    $cashRefundAmount = 0.00;
                    $refundStatus = 'debt_reduced';
                    $chosenMethod = 'Debt Reduction (Kupunguza Deni)';
                } else {
                    // Partial debt reduction, remainder refunded via selected method
                    $debtReducedAmount = $saleBalanceDue;
                    $cashRefundAmount = $refundAmount - $saleBalanceDue;
                    $refundStatus = 'refunded';
                }

                // Update invoice balance due
                $newBalanceDue = max(0.00, $saleBalanceDue - $debtReducedAmount);
                $newPaymentStatus = ($newBalanceDue <= 0.001) ? 'paid' : $sale['payment_status'];

                $upSale = $pdo->prepare("UPDATE `sales` SET balance_due = ?, payment_status = ?, updated_at = NOW() WHERE id = ?");
                $upSale->execute([$newBalanceDue, $newPaymentStatus, $saleId]);

                // Reduce customer current_balance
                if ($customerId) {
                    $upCust = $pdo->prepare("UPDATE `customers` SET current_balance = GREATEST(0.00, current_balance - ?), updated_at = NOW() WHERE id = ?");
                    $upCust->execute([$debtReducedAmount, $customerId]);
                }
            } else {
                // Sale was fully paid, handle refund methods
                if ($chosenMethod === 'Customer Credit' || $chosenMethod === 'Account Balance') {
                    if (!$customerId) {
                        throw new Exception("Huwezi kuweka Customer Credit kwa mteja wa kawaida asiyesajiliwa (Walk-in). Chagua Cash Refund.");
                    }
                    $customerCreditAmount = $refundAmount;
                    $refundStatus = 'credit_issued';

                    // Reduce customer current balance (or if balance was 0, it acts as credit/advance)
                    $upCust = $pdo->prepare("UPDATE `customers` SET current_balance = current_balance - ?, updated_at = NOW() WHERE id = ?");
                    $upCust->execute([$customerCreditAmount, $customerId]);
                } else {
                    $cashRefundAmount = $refundAmount;
                    $refundStatus = 'refunded';
                }
            }

            // Insert into `sale_payments` as negative/refund record for financial audit
            $stmtPay = $pdo->prepare("
                INSERT INTO `sale_payments` (
                    payment_no, sale_id, customer_id, amount, payment_method,
                    provider, reference_no, payment_date, received_by, notes, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, NOW(), ?, ?, 'completed', NOW()
                )
            ");
            $stmtPay->execute([
                $refundRefNo,
                $saleId,
                $customerId,
                -$refundAmount, // Negative amount indicates refund/reversal
                $chosenMethod,
                $provider,
                $return['return_number'],
                $userId,
                "Marejesho ya fedha (Refund) kwa ajili ya Rejesho #{$return['return_number']}" .
                ($debtReducedAmount > 0 ? " (Deni limepunguzwa TSh " . number_format($debtReducedAmount, 2) . ")" : "") .
                ($cashRefundAmount > 0 ? " (Pesa zilizolipwa TSh " . number_format($cashRefundAmount, 2) . ")" : "")
            ]);

            // Check if entire sale is now fully returned
            $returnableLeft = self::getReturnableItemsForSale($saleId);
            $totalRemainingUnits = 0;
            foreach ($returnableLeft as $rl) {
                $totalRemainingUnits += (int)$rl['remaining_returnable'];
            }

            $saleStatusUpdate = ($totalRemainingUnits === 0) ? 'refunded' : 'partially_refunded';
            $upSaleStatus = $pdo->prepare("UPDATE `sales` SET sale_status = ?, updated_at = NOW() WHERE id = ?");
            $upSaleStatus->execute([$saleStatusUpdate, $saleId]);

            // Update return status
            $newReturnStatus = ((int)$return['is_restocked'] === 1) ? 'completed' : 'refunded';
            $upRet = $pdo->prepare("
                UPDATE `returns`
                SET status = ?, refund_method = ?, refund_provider = ?, refund_reference_no = ?,
                    refund_status = ?, refunded_by = ?, refunded_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $upRet->execute([
                $newReturnStatus,
                $chosenMethod,
                $provider,
                $refundRefNo,
                $refundStatus,
                $userId,
                $returnId
            ]);

            // Audit Log
            Helper::logAudit(
                'RETURN_REFUNDED',
                'returns',
                $returnId,
                ['status' => $return['status'], 'refund_status' => 'unrefunded'],
                [
                    'status'              => $newReturnStatus,
                    'refund_status'       => $refundStatus,
                    'refund_reference_no' => $refundRefNo,
                    'refund_method'       => $chosenMethod,
                    'refund_amount'       => $refundAmount,
                    'debt_reduced'        => $debtReducedAmount,
                    'cash_refunded'       => $cashRefundAmount,
                    'sale_status'         => $saleStatusUpdate
                ]
            );

            $pdo->commit();

            return [
                'success'             => true,
                'status'              => $newReturnStatus,
                'refund_status'       => $refundStatus,
                'refund_reference_no' => $refundRefNo,
                'refund_amount'       => $refundAmount,
                'debt_reduced'        => $debtReducedAmount,
                'cash_refunded'       => $cashRefundAmount,
                'sale_status'         => $saleStatusUpdate
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancel a return with safety checks and reversals
     */
    public static function cancelReturn(int $returnId, string $reason, int $userId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("SELECT * FROM `returns` WHERE id = ? FOR UPDATE");
            $stmt->execute([$returnId]);
            $return = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$return) {
                throw new Exception("Rejesho halikupatikana.");
            }

            if ($return['status'] === 'cancelled') {
                throw new Exception("Rejesho hili tayari limeshaghairiwa (Already cancelled).");
            }

            // If stock was already restocked, reverse stock movements
            if ((int)$return['is_restocked'] === 1) {
                $itemsStmt = $pdo->prepare("SELECT * FROM `return_items` WHERE return_id = ? AND is_restocked = 1");
                $itemsStmt->execute([$returnId]);
                $restockedItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($restockedItems as $rit) {
                    Inventory::recordMovement(
                        (int)$rit['product_id'],
                        'adjustment_out',
                        -(int)$rit['quantity'],
                        'return_cancellation',
                        $returnId,
                        "Kughairi rejesho #{$return['return_number']}. Sababu: {$reason}",
                        (float)$rit['unit_cost'],
                        $userId,
                        $pdo
                    );
                }
            }

            // If refund was already issued, reverse refund financial adjustments
            if ($return['refund_status'] !== 'unrefunded' && !empty($return['refund_reference_no'])) {
                // Reverse payment record
                $upPay = $pdo->prepare("
                    UPDATE `sale_payments` 
                    SET status = 'reversed', reversal_reason = ?, reversed_by = ?, reversed_at = NOW() 
                    WHERE reference_no = ?
                ");
                $upPay->execute(["Kughairi rejesho: {$reason}", $userId, $return['return_number']]);

                // If customer balance was reduced or debt was reduced, restore original balance
                $refundAmt = (float)$return['refund_amount'];
                if ($return['refund_status'] === 'debt_reduced' && !empty($return['customer_id'])) {
                    $pdo->prepare("UPDATE `customers` SET current_balance = current_balance + ?, updated_at = NOW() WHERE id = ?")
                        ->execute([$refundAmt, (int)$return['customer_id']]);
                    $pdo->prepare("UPDATE `sales` SET balance_due = balance_due + ?, updated_at = NOW() WHERE id = ?")
                        ->execute([$refundAmt, (int)$return['sale_id']]);
                }
            }

            // Update sale status back to completed if needed
            $upSale = $pdo->prepare("UPDATE `sales` SET sale_status = 'completed', updated_at = NOW() WHERE id = ?");
            $upSale->execute([(int)$return['sale_id']]);

            // Update return status
            $uRet = $pdo->prepare("
                UPDATE `returns`
                SET status = 'cancelled', cancelled_by = ?, cancelled_at = NOW(), cancel_reason = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $uRet->execute([$userId, $reason, $returnId]);

            Helper::logAudit(
                'RETURN_CANCELLED',
                'returns',
                $returnId,
                ['status' => $return['status']],
                ['status' => 'cancelled', 'reason' => $reason]
            );

            $pdo->commit();
            return ['success' => true, 'status' => 'cancelled'];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Reject a return request
     */
    public static function rejectReturn(int $returnId, string $reason, int $userId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("SELECT * FROM `returns` WHERE id = ? FOR UPDATE");
            $stmt->execute([$returnId]);
            $return = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$return) {
                throw new Exception("Rejesho halikupatikana.");
            }

            if (!in_array($return['status'], ['draft', 'requested', 'approved'])) {
                throw new Exception("Huwezi kukataa rejesho lenye hadhi ya: {$return['status']}.");
            }

            $uRet = $pdo->prepare("
                UPDATE `returns`
                SET status = 'rejected', cancel_reason = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $uRet->execute([$reason, $returnId]);

            Helper::logAudit(
                'RETURN_REJECTED',
                'returns',
                $returnId,
                ['status' => $return['status']],
                ['status' => 'rejected', 'reason' => $reason]
            );

            $pdo->commit();
            return ['success' => true, 'status' => 'rejected'];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Find Return by ID with related sale, customer, and user information
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT r.*,
                   s.invoice_no, s.sale_date, s.total_amount as sale_total_amount,
                   s.paid_amount as sale_paid_amount, s.balance_due as sale_balance_due,
                   s.payment_method as sale_payment_method, s.sale_status,
                   c.name as customer_name, c.customer_code, c.phone as customer_phone,
                   u_cr.full_name as created_by_name,
                   u_ap.full_name as approved_by_name,
                   u_rc.full_name as received_by_name,
                   u_rf.full_name as refunded_by_name,
                   u_cn.full_name as cancelled_by_name
            FROM `returns` r
            JOIN `sales` s ON r.sale_id = s.id
            LEFT JOIN `customers` c ON r.customer_id = c.id
            LEFT JOIN `users` u_cr ON r.created_by = u_cr.id
            LEFT JOIN `users` u_ap ON r.approved_by = u_ap.id
            LEFT JOIN `users` u_rc ON r.received_by = u_rc.id
            LEFT JOIN `users` u_rf ON r.refunded_by = u_rf.id
            LEFT JOIN `users` u_cn ON r.cancelled_by = u_cn.id
            WHERE r.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Find Return by Return Number
     */
    public static function findByNumber(string $returnNumber): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT r.*, s.invoice_no
            FROM `returns` r
            JOIN `sales` s ON r.sale_id = s.id
            WHERE r.return_number = ?
            LIMIT 1
        ");
        $stmt->execute([trim($returnNumber)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Get items of a return
     */
    public static function getItems(int $returnId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT ri.*,
                   p.name as live_product_name, p.sku as live_product_sku,
                   u.short_name as unit_name, b.name as brand_name, cat.name as category_name
            FROM `return_items` ri
            LEFT JOIN `products` p ON ri.product_id = p.id
            LEFT JOIN `units` u ON p.unit_id = u.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            LEFT JOIN `categories` cat ON p.category_id = cat.id
            WHERE ri.return_id = ?
            ORDER BY ri.id ASC
        ");
        $stmt->execute([$returnId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Filterable, paginated list of returns
     */
    public static function all(array $filters = [], int $page = 1, int $perPage = 20): array {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT r.*,
                   s.invoice_no,
                   c.name as customer_name, c.customer_code,
                   u.full_name as created_by_name,
                   (SELECT COUNT(*) FROM `return_items` WHERE return_id = r.id) as items_count,
                   (SELECT COALESCE(SUM(quantity), 0) FROM `return_items` WHERE return_id = r.id) as total_units_returned
            FROM `returns` r
            JOIN `sales` s ON r.sale_id = s.id
            LEFT JOIN `customers` c ON r.customer_id = c.id
            LEFT JOIN `users` u ON r.created_by = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%" . trim($filters['search']) . "%";
            $sql .= " AND (r.return_number LIKE ? OR s.invoice_no LIKE ? OR c.name LIKE ? OR c.customer_code LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term]);
        }

        if (!empty($filters['status'])) {
            $sql .= " AND r.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['return_type'])) {
            $sql .= " AND r.return_type = ?";
            $params[] = $filters['return_type'];
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND r.customer_id = ?";
            $params[] = (int)$filters['customer_id'];
        }

        if (!empty($filters['created_by'])) {
            $sql .= " AND r.created_by = ?";
            $params[] = (int)$filters['created_by'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND r.return_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND r.return_date <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY r.created_at DESC, r.id DESC LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count returns matching filters
     */
    public static function count(array $filters = []): int {
        $pdo = Database::getConnection();

        $sql = "
            SELECT COUNT(r.id)
            FROM `returns` r
            JOIN `sales` s ON r.sale_id = s.id
            LEFT JOIN `customers` c ON r.customer_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%" . trim($filters['search']) . "%";
            $sql .= " AND (r.return_number LIKE ? OR s.invoice_no LIKE ? OR c.name LIKE ? OR c.customer_code LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term]);
        }

        if (!empty($filters['status'])) {
            $sql .= " AND r.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['return_type'])) {
            $sql .= " AND r.return_type = ?";
            $params[] = $filters['return_type'];
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND r.customer_id = ?";
            $params[] = (int)$filters['customer_id'];
        }

        if (!empty($filters['created_by'])) {
            $sql .= " AND r.created_by = ?";
            $params[] = (int)$filters['created_by'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND r.return_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND r.return_date <= ?";
            $params[] = $filters['date_to'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get Returns Dashboard Statistics
     */
    public static function getDashboardStats(): array {
        $pdo = Database::getConnection();

        // 1. Returns Counts & Values by timeframe
        $today = date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime('monday this week'));
        $monthStart = date('Y-m-01');

        $statsQuery = "
            SELECT 
                COUNT(CASE WHEN return_date = '{$today}' AND status != 'cancelled' THEN 1 END) as returns_today_count,
                COALESCE(SUM(CASE WHEN return_date = '{$today}' AND status != 'cancelled' THEN refund_amount ELSE 0 END), 0.00) as returns_today_value,
                COUNT(CASE WHEN return_date >= '{$weekStart}' AND status != 'cancelled' THEN 1 END) as returns_week_count,
                COALESCE(SUM(CASE WHEN return_date >= '{$weekStart}' AND status != 'cancelled' THEN refund_amount ELSE 0 END), 0.00) as returns_week_value,
                COUNT(CASE WHEN return_date >= '{$monthStart}' AND status != 'cancelled' THEN 1 END) as returns_month_count,
                COALESCE(SUM(CASE WHEN return_date >= '{$monthStart}' AND status != 'cancelled' THEN refund_amount ELSE 0 END), 0.00) as returns_month_value,
                COUNT(CASE WHEN status = 'requested' THEN 1 END) as pending_approval_count,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_pending_restock_count,
                COUNT(CASE WHEN status IN ('received', 'refunded') THEN 1 END) as pending_refund_count,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_count,
                COALESCE(SUM(CASE WHEN status != 'cancelled' THEN refund_amount ELSE 0 END), 0.00) as total_returned_value
            FROM `returns`
        ";
        $retStats = $pdo->query($statsQuery)->fetch(PDO::FETCH_ASSOC);

        // 2. Condition breakdown across return_items
        $condQuery = "
            SELECT 
                COALESCE(SUM(CASE WHEN ri.condition_status = 'resellable' AND r.status != 'cancelled' THEN ri.quantity ELSE 0 END), 0) as resellable_units,
                COALESCE(SUM(CASE WHEN ri.condition_status = 'damaged' AND r.status != 'cancelled' THEN ri.quantity ELSE 0 END), 0) as damaged_units,
                COALESCE(SUM(CASE WHEN ri.condition_status = 'defective' AND r.status != 'cancelled' THEN ri.quantity ELSE 0 END), 0) as defective_units,
                COALESCE(SUM(CASE WHEN ri.condition_status = 'not_resellable' AND r.status != 'cancelled' THEN ri.quantity ELSE 0 END), 0) as not_resellable_units,
                COALESCE(SUM(CASE WHEN r.status != 'cancelled' THEN ri.quantity ELSE 0 END), 0) as total_returned_units
            FROM `return_items` ri
            JOIN `returns` r ON ri.return_id = r.id
        ";
        $condStats = $pdo->query($condQuery)->fetch(PDO::FETCH_ASSOC);

        // Merge stats
        return array_merge($retStats, $condStats);
    }

    /**
     * Product Return Statistics & Return Rate Analysis
     */
    public static function getProductReturnStats(int $productId): array {
        $pdo = Database::getConnection();

        // 1. Total units sold
        $soldStmt = $pdo->prepare("
            SELECT COALESCE(SUM(si.quantity), 0) as total_units_sold,
                   COALESCE(SUM(si.subtotal), 0.00) as total_sales_revenue
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            WHERE si.product_id = ? AND s.sale_status != 'cancelled'
        ");
        $soldStmt->execute([$productId]);
        $soldData = $soldStmt->fetch(PDO::FETCH_ASSOC);
        $unitsSold = (int)$soldData['total_units_sold'];

        // 2. Return statistics
        $retStmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT r.id) as return_count,
                COALESCE(SUM(ri.quantity), 0) as total_units_returned,
                COALESCE(SUM(ri.subtotal), 0.00) as total_return_value,
                COALESCE(SUM(CASE WHEN ri.condition_status = 'resellable' THEN ri.quantity ELSE 0 END), 0) as resellable_units,
                COALESCE(SUM(CASE WHEN ri.condition_status = 'damaged' THEN ri.quantity ELSE 0 END), 0) as damaged_units,
                COALESCE(SUM(CASE WHEN ri.condition_status = 'defective' THEN ri.quantity ELSE 0 END), 0) as defective_units,
                COALESCE(SUM(CASE WHEN ri.condition_status = 'not_resellable' THEN ri.quantity ELSE 0 END), 0) as not_resellable_units
            FROM `return_items` ri
            JOIN `returns` r ON ri.return_id = r.id
            WHERE ri.product_id = ? AND r.status != 'cancelled'
        ");
        $retStmt->execute([$productId]);
        $retData = $retStmt->fetch(PDO::FETCH_ASSOC);
        $unitsReturned = (int)$retData['total_units_returned'];

        // Calculate Return Rate % (prevent division by zero)
        $returnRate = ($unitsSold > 0) ? round(($unitsReturned / $unitsSold) * 100, 2) : 0.00;

        return array_merge($soldData, $retData, [
            'return_rate_percent' => $returnRate
        ]);
    }

    /**
     * Product Return History list
     */
    public static function getProductReturnHistory(int $productId, int $limit = 20): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT r.id, r.return_number, r.return_date, r.status, r.refund_method,
                   s.invoice_no, c.name as customer_name,
                   ri.quantity as return_quantity, ri.unit_refund_price, ri.subtotal as return_item_subtotal,
                   ri.condition_status, ri.return_reason, ri.is_restocked,
                   u.full_name as created_by_name
            FROM `return_items` ri
            JOIN `returns` r ON ri.return_id = r.id
            JOIN `sales` s ON r.sale_id = s.id
            LEFT JOIN `customers` c ON r.customer_id = c.id
            LEFT JOIN `users` u ON r.created_by = u.id
            WHERE ri.product_id = ? AND r.status != 'cancelled'
            ORDER BY r.return_date DESC, r.id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $productId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Customer Return History list
     */
    public static function getCustomerReturnHistory(int $customerId, int $limit = 50): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT r.*,
                   s.invoice_no,
                   u.full_name as created_by_name,
                   (SELECT COUNT(*) FROM `return_items` WHERE return_id = r.id) as items_count,
                   (SELECT COALESCE(SUM(quantity), 0) FROM `return_items` WHERE return_id = r.id) as total_units_returned
            FROM `returns` r
            JOIN `sales` s ON r.sale_id = s.id
            LEFT JOIN `users` u ON r.created_by = u.id
            WHERE r.customer_id = ?
            ORDER BY r.return_date DESC, r.id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $customerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Multi-dimensional Return Reports & Return Rate Analysis
     */
    public static function getReturnReports(array $filters = []): array {
        $pdo = Database::getConnection();

        $groupBy = $filters['group_by'] ?? 'product';
        $params = [];
        $where = "r.status != 'cancelled'";

        if (!empty($filters['date_from'])) {
            $where .= " AND r.return_date >= ?";
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND r.return_date <= ?";
            $params[] = $filters['date_to'];
        }

        switch ($groupBy) {
            case 'date':
                $sql = "
                    SELECT 
                        r.return_date as report_key,
                        r.return_date as label,
                        COUNT(DISTINCT r.id) as total_returns_count,
                        COALESCE(SUM(ri.quantity), 0) as total_units_returned,
                        COALESCE(SUM(ri.subtotal), 0.00) as total_returned_value,
                        COALESCE(SUM(CASE WHEN ri.condition_status = 'resellable' THEN ri.quantity ELSE 0 END), 0) as resellable_units,
                        COALESCE(SUM(CASE WHEN ri.condition_status IN ('damaged', 'defective', 'not_resellable') THEN ri.quantity ELSE 0 END), 0) as damaged_defective_units
                    FROM `returns` r
                    JOIN `return_items` ri ON r.id = ri.return_id
                    WHERE {$where}
                    GROUP BY r.return_date
                    ORDER BY r.return_date DESC
                ";
                break;

            case 'category':
                $sql = "
                    SELECT 
                        cat.id as report_key,
                        cat.name as label,
                        COUNT(DISTINCT r.id) as total_returns_count,
                        COALESCE(SUM(ri.quantity), 0) as total_units_returned,
                        COALESCE(SUM(ri.subtotal), 0.00) as total_returned_value,
                        COALESCE(SUM(CASE WHEN ri.condition_status = 'resellable' THEN ri.quantity ELSE 0 END), 0) as resellable_units,
                        COALESCE(SUM(CASE WHEN ri.condition_status IN ('damaged', 'defective', 'not_resellable') THEN ri.quantity ELSE 0 END), 0) as damaged_defective_units
                    FROM `returns` r
                    JOIN `return_items` ri ON r.id = ri.return_id
                    JOIN `products` p ON ri.product_id = p.id
                    JOIN `categories` cat ON p.category_id = cat.id
                    WHERE {$where}
                    GROUP BY cat.id, cat.name
                    ORDER BY total_returned_value DESC
                ";
                break;

            case 'brand':
                $sql = "
                    SELECT 
                        b.id as report_key,
                        COALESCE(b.name, 'Bila Brand') as label,
                        COUNT(DISTINCT r.id) as total_returns_count,
                        COALESCE(SUM(ri.quantity), 0) as total_units_returned,
                        COALESCE(SUM(ri.subtotal), 0.00) as total_returned_value,
                        COALESCE(SUM(CASE WHEN ri.condition_status = 'resellable' THEN ri.quantity ELSE 0 END), 0) as resellable_units,
                        COALESCE(SUM(CASE WHEN ri.condition_status IN ('damaged', 'defective', 'not_resellable') THEN ri.quantity ELSE 0 END), 0) as damaged_defective_units
                    FROM `returns` r
                    JOIN `return_items` ri ON r.id = ri.return_id
                    JOIN `products` p ON ri.product_id = p.id
                    LEFT JOIN `brands` b ON p.brand_id = b.id
                    WHERE {$where}
                    GROUP BY b.id, b.name
                    ORDER BY total_returned_value DESC
                ";
                break;

            case 'customer':
                $sql = "
                    SELECT 
                        c.id as report_key,
                        COALESCE(c.name, 'Mteja wa Kawaida (Walk-in)') as label,
                        c.customer_code,
                        COUNT(DISTINCT r.id) as total_returns_count,
                        COALESCE(SUM(ri.quantity), 0) as total_units_returned,
                        COALESCE(SUM(ri.subtotal), 0.00) as total_returned_value
                    FROM `returns` r
                    JOIN `return_items` ri ON r.id = ri.return_id
                    LEFT JOIN `customers` c ON r.customer_id = c.id
                    WHERE {$where}
                    GROUP BY c.id, c.name, c.customer_code
                    ORDER BY total_returned_value DESC
                ";
                break;

            case 'reason':
                $sql = "
                    SELECT 
                        r.return_type as report_key,
                        r.return_type as label,
                        COUNT(DISTINCT r.id) as total_returns_count,
                        COALESCE(SUM(ri.quantity), 0) as total_units_returned,
                        COALESCE(SUM(ri.subtotal), 0.00) as total_returned_value
                    FROM `returns` r
                    JOIN `return_items` ri ON r.id = ri.return_id
                    WHERE {$where}
                    GROUP BY r.return_type
                    ORDER BY total_returns_count DESC
                ";
                break;

            case 'product':
            default:
                // Return Rate Analysis Table
                $sql = "
                    SELECT 
                        p.id as report_key,
                        p.name as label,
                        p.sku,
                        cat.name as category_name,
                        b.name as brand_name,
                        COALESCE((
                            SELECT SUM(si.quantity) 
                            FROM `sale_items` si 
                            JOIN `sales` s ON si.sale_id = s.id 
                            WHERE si.product_id = p.id AND s.sale_status != 'cancelled'
                        ), 0) as total_units_sold,
                        COUNT(DISTINCT r.id) as total_returns_count,
                        COALESCE(SUM(ri.quantity), 0) as total_units_returned,
                        COALESCE(SUM(ri.subtotal), 0.00) as total_returned_value,
                        COALESCE(SUM(CASE WHEN ri.condition_status = 'resellable' THEN ri.quantity ELSE 0 END), 0) as resellable_units,
                        COALESCE(SUM(CASE WHEN ri.condition_status = 'damaged' THEN ri.quantity ELSE 0 END), 0) as damaged_units,
                        COALESCE(SUM(CASE WHEN ri.condition_status = 'defective' THEN ri.quantity ELSE 0 END), 0) as defective_units
                    FROM `returns` r
                    JOIN `return_items` ri ON r.id = ri.return_id
                    JOIN `products` p ON ri.product_id = p.id
                    LEFT JOIN `categories` cat ON p.category_id = cat.id
                    LEFT JOIN `brands` b ON p.brand_id = b.id
                    WHERE {$where}
                    GROUP BY p.id, p.name, p.sku, cat.name, b.name
                    ORDER BY total_units_returned DESC
                ";
                break;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute return rates if product group
        if ($groupBy === 'product') {
            foreach ($rows as &$row) {
                $sold = (int)$row['total_units_sold'];
                $ret = (int)$row['total_units_returned'];
                $row['return_rate'] = ($sold > 0) ? round(($ret / $sold) * 100, 2) : 0.00;
            }
        }

        return $rows;
    }
}
