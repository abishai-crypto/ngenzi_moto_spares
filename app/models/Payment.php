<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Payment Model (Phase 9)
 * Handles Sales Payments, Debt Collections, Allocations, Controlled Reversals & Concurrency
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helper;
use PDO;
use Exception;

class Payment {

    /**
     * Generate unique payment reference (e.g. PAY-000001)
     * Validated server-side with row-level locking
     */
    public static function generatePaymentNo(?PDO $pdo = null): string {
        $pdo = $pdo ?? Database::getConnection();
        $prefix = Setting::get('payment_receipt_prefix', 'PAY-');

        // Check highest sequence number in both sale_payments and customer_payments
        $stmt1 = $pdo->query("SELECT payment_no FROM `sale_payments` WHERE payment_no LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1");
        $last1 = $stmt1->fetchColumn();

        $stmt2 = $pdo->query("SELECT payment_no FROM `customer_payments` WHERE payment_no LIKE '{$prefix}%' ORDER BY id DESC LIMIT 1");
        $last2 = $stmt2->fetchColumn();

        $maxNum = 0;
        foreach ([$last1, $last2] as $ref) {
            if ($ref && preg_match('/' . preg_quote($prefix, '/') . '(\d+)/', $ref, $matches)) {
                $num = (int)$matches[1];
                if ($num > $maxNum) {
                    $maxNum = $num;
                }
            }
        }

        $nextNum = $maxNum + 1;
        $candidate = $prefix . str_pad((string)$nextNum, 6, '0', STR_PAD_LEFT);

        // Verify uniqueness
        while (true) {
            $chk1 = $pdo->prepare("SELECT COUNT(*) FROM `sale_payments` WHERE payment_no = ?");
            $chk1->execute([$candidate]);
            $exists1 = (int)$chk1->fetchColumn() > 0;

            $chk2 = $pdo->prepare("SELECT COUNT(*) FROM `customer_payments` WHERE payment_no = ?");
            $chk2->execute([$candidate]);
            $exists2 = (int)$chk2->fetchColumn() > 0;

            if (!$exists1 && !$exists2) {
                break;
            }
            $nextNum++;
            $candidate = $prefix . str_pad((string)$nextNum, 6, '0', STR_PAD_LEFT);
        }

        return $candidate;
    }

    /**
     * Record a payment against an invoice (Sale) within an ACID transaction
     * Row locks invoice to prevent race conditions and payment overflow
     */
    public static function recordSalePayment(int $saleId, array $paymentData, int $userId): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Row-lock the sale record
            $sStmt = $pdo->prepare("
                SELECT id, invoice_no, customer_id, customer_name, customer_phone,
                       total_amount, paid_amount, balance_due, payment_status, sale_status
                FROM `sales`
                WHERE id = ?
                FOR UPDATE
            ");
            $sStmt->execute([$saleId]);
            $sale = $sStmt->fetch(PDO::FETCH_ASSOC);

            if (!$sale) {
                throw new Exception("Ankara ya mauzo yenye ID #{$saleId} haikupatikana.");
            }

            if ($sale['sale_status'] === 'cancelled') {
                throw new Exception("Huwezi kupokea malipo kwa ankara iliyoghairiwa (Cancelled Sale #{$sale['invoice_no']}).");
            }

            $currentBalanceDue = (float)$sale['balance_due'];
            if ($currentBalanceDue <= 0.00) {
                throw new Exception("Ankara hii (#{$sale['invoice_no']}) tayari imeshalipwa kikamilifu (Paid in Full).");
            }

            $amount = (float)($paymentData['amount'] ?? 0.00);
            if ($amount <= 0.00) {
                throw new Exception("Kiasi cha malipo lazima kiwe zaidi ya TSh 0.00.");
            }

            // 2. Overpayment protection: Reject if amount exceeds balance due
            // (Round to 2 decimal places to prevent floating point inaccuracy)
            if (round($amount, 2) > round($currentBalanceDue, 2)) {
                throw new Exception("Kiasi cha malipo (TSh " . number_format($amount, 2) . ") kinazidi salio linalodaiwa kwenye ankara hii (TSh " . number_format($currentBalanceDue, 2) . "). Mfumo hauruhusu malipo kupitiliza.");
            }

            $paymentMethod = trim($paymentData['payment_method'] ?? 'Cash');
            $provider = !empty($paymentData['provider']) ? trim($paymentData['provider']) : null;
            $referenceNo = !empty($paymentData['reference_no']) ? trim($paymentData['reference_no']) : null;
            $paymentDate = !empty($paymentData['payment_date']) ? $paymentData['payment_date'] : date('Y-m-d H:i:s');
            $notes = !empty($paymentData['notes']) ? trim($paymentData['notes']) : null;

            // Security: Prevent duplicate payment reference numbers across payments
            if (!empty($referenceNo)) {
                $dupStmt = $pdo->prepare("
                    SELECT payment_no FROM `sale_payments` WHERE reference_no = ? AND status != 'cancelled'
                    UNION
                    SELECT payment_no FROM `customer_payments` WHERE reference_no = ? AND status != 'cancelled'
                    LIMIT 1
                ");
                $dupStmt->execute([$referenceNo, $referenceNo]);
                $existingRef = $dupStmt->fetchColumn();
                if ($existingRef) {
                    throw new Exception("Nambari ya kumbukumbu (Reference #{$referenceNo}) tayari imeshatumika kwenye malipo #{$existingRef}. Ombi limekataliwa kuzuia malipo mara mbili (Duplicate Transaction).");
                }
            }

            // Security: Prevent rapid duplicate submission (double-clicks on same sale)
            $rapidStmt = $pdo->prepare("
                SELECT payment_no FROM `sale_payments`
                WHERE sale_id = ? AND amount = ? AND payment_method = ? AND created_at >= (NOW() - INTERVAL 5 SECOND)
                LIMIT 1
            ");
            $rapidStmt->execute([$saleId, $amount, $paymentMethod]);
            if ($rapidPay = $rapidStmt->fetchColumn()) {
                throw new Exception("Malipo yanayofanana kwa ankara hii yamepokelewa hivi punde (Risiti #{$rapidPay}). Tafadhali subiri ili kuzuia malipo mara mbili.");
            }

            // Generate unique payment number
            $paymentNo = self::generatePaymentNo($pdo);

            // 3. Insert payment into sale_payments
            $pStmt = $pdo->prepare("
                INSERT INTO `sale_payments` (
                    payment_no, sale_id, customer_id, amount, payment_method,
                    provider, reference_no, payment_date, received_by, notes, status
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, 'completed'
                )
            ");
            $customerId = !empty($sale['customer_id']) ? (int)$sale['customer_id'] : null;
            $pStmt->execute([
                $paymentNo, $saleId, $customerId, $amount, $paymentMethod,
                $provider, $referenceNo, $paymentDate, $userId, $notes
            ]);
            $paymentId = (int)$pdo->lastInsertId();

            // 4. Update sales table
            $newPaidAmount = (float)$sale['paid_amount'] + $amount;
            $newBalanceDue = max(0.00, (float)$sale['total_amount'] - $newPaidAmount);
            $newPaymentStatus = ($newBalanceDue <= 0.001) ? 'paid' : 'partially_paid';

            $uStmt = $pdo->prepare("
                UPDATE `sales`
                SET paid_amount = ?, balance_due = ?, payment_status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $uStmt->execute([$newPaidAmount, $newBalanceDue, $newPaymentStatus, $saleId]);

            // 5. Update customer balance if customer account is linked
            if ($customerId) {
                // Row lock customer
                $cStmt = $pdo->prepare("SELECT id, current_balance FROM `customers` WHERE id = ? FOR UPDATE");
                $cStmt->execute([$customerId]);
                $customer = $cStmt->fetch(PDO::FETCH_ASSOC);

                if ($customer) {
                    $newCustBalance = max(0.00, (float)$customer['current_balance'] - $amount);
                    $updCust = $pdo->prepare("UPDATE `customers` SET current_balance = ?, updated_at = NOW() WHERE id = ?");
                    $updCust->execute([$newCustBalance, $customerId]);
                }
            }

            // 6. Audit Trail
            Helper::logAudit('PAYMENT_CREATED', 'payments', $paymentId, null, [
                'payment_no'       => $paymentNo,
                'sale_id'          => $saleId,
                'invoice_no'       => $sale['invoice_no'],
                'customer_id'      => $customerId,
                'customer_name'    => $sale['customer_name'],
                'amount'           => $amount,
                'payment_method'   => $paymentMethod,
                'provider'         => $provider,
                'reference_no'     => $referenceNo,
                'previous_balance' => $currentBalanceDue,
                'new_balance'      => $newBalanceDue,
                'new_status'       => $newPaymentStatus
            ]);

            $pdo->commit();

            return [
                'success'           => true,
                'payment_id'        => $paymentId,
                'payment_no'        => $paymentNo,
                'sale_id'           => $saleId,
                'invoice_no'        => $sale['invoice_no'],
                'amount_paid'       => $amount,
                'new_paid_total'    => $newPaidAmount,
                'new_balance_due'   => $newBalanceDue,
                'new_status'        => $newPaymentStatus,
                'customer_id'       => $customerId
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Customer Debt Collection Workflow
     * Collects payment from customer and allocates across outstanding invoices (FIFO or custom allocation)
     */
    public static function collectDebt(
        int $customerId,
        float $amount,
        string $paymentMethod,
        ?string $provider,
        ?string $referenceNo,
        ?string $notes,
        ?array $customAllocation,
        int $userId
    ): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            if ($amount <= 0.00) {
                throw new Exception("Kiasi cha kulipa deni lazima kiwe zaidi ya TSh 0.00.");
            }

            // Security: Prevent duplicate payment reference numbers across payments
            if (!empty($referenceNo)) {
                $dupStmt = $pdo->prepare("
                    SELECT payment_no FROM `sale_payments` WHERE reference_no = ? AND status != 'cancelled'
                    UNION
                    SELECT payment_no FROM `customer_payments` WHERE reference_no = ? AND status != 'cancelled'
                    LIMIT 1
                ");
                $dupStmt->execute([$referenceNo, $referenceNo]);
                $existingRef = $dupStmt->fetchColumn();
                if ($existingRef) {
                    throw new Exception("Nambari ya kumbukumbu (Reference #{$referenceNo}) tayari imeshatumika kwenye malipo #{$existingRef}. Ombi limekataliwa kuzuia malipo mara mbili (Duplicate Transaction).");
                }
            }

            // 1. Row-lock customer
            $cStmt = $pdo->prepare("SELECT id, customer_code, name, phone, current_balance, credit_limit, status FROM `customers` WHERE id = ? FOR UPDATE");
            $cStmt->execute([$customerId]);
            $customer = $cStmt->fetch(PDO::FETCH_ASSOC);

            if (!$customer) {
                throw new Exception("Mteja mwenye ID #{$customerId} hakupatikana.");
            }

            $currentTotalDebt = (float)$customer['current_balance'];

            // 2. Fetch all outstanding invoices of this customer with row-level locks
            $invStmt = $pdo->prepare("
                SELECT id, invoice_no, total_amount, paid_amount, balance_due, payment_status, due_date, sale_date
                FROM `sales`
                WHERE customer_id = ? AND balance_due > 0 AND sale_status != 'cancelled'
                ORDER BY sale_date ASC, id ASC
                FOR UPDATE
            ");
            $invStmt->execute([$customerId]);
            $outstandingInvoices = $invStmt->fetchAll(PDO::FETCH_ASSOC);

            $calculatedTotalDebt = 0.00;
            foreach ($outstandingInvoices as $inv) {
                $calculatedTotalDebt += (float)$inv['balance_due'];
            }

            // Prevent payment overflow on total outstanding debt
            if (round($amount, 2) > round($calculatedTotalDebt, 2)) {
                throw new Exception("Kiasi kilichowekwa (TSh " . number_format($amount, 2) . ") kinazidi jumla ya deni lote la mteja (TSh " . number_format($calculatedTotalDebt, 2) . "). Mfumo hauruhusu malipo kupitiliza deni lililopo.");
            }

            // 3. Generate Master Payment Number for this collection
            $masterPaymentNo = self::generatePaymentNo($pdo);
            $paymentDate = date('Y-m-d H:i:s');

            // 4. Allocate payment across invoices
            $remainingToAllocate = $amount;
            $allocatedItems = [];

            if (!empty($customAllocation)) {
                // Manual explicit allocation
                foreach ($customAllocation as $alloc) {
                    $targetSaleId = (int)($alloc['sale_id'] ?? 0);
                    $allocAmount = (float)($alloc['amount'] ?? 0.00);

                    if ($allocAmount <= 0.00) continue;

                    // Match with locked invoice
                    $matchedIndex = null;
                    foreach ($outstandingInvoices as $idx => $inv) {
                        if ((int)$inv['id'] === $targetSaleId) {
                            $matchedIndex = $idx;
                            break;
                        }
                    }

                    if ($matchedIndex === null) {
                        throw new Exception("Ankara yenye ID #{$targetSaleId} si sahihi au haina salio linalodaiwa.");
                    }

                    $inv = $outstandingInvoices[$matchedIndex];
                    if (round($allocAmount, 2) > round((float)$inv['balance_due'], 2)) {
                        throw new Exception("Kiasi cha TSh " . number_format($allocAmount, 2) . " kinazidi salio la ankara #{$inv['invoice_no']} (TSh " . number_format((float)$inv['balance_due'], 2) . ").");
                    }

                    $allocatedItems[] = [
                        'sale'   => $inv,
                        'amount' => $allocAmount
                    ];
                    $remainingToAllocate -= $allocAmount;
                }

                if (abs($remainingToAllocate) > 0.01) {
                    throw new Exception("Mgawanyo wa malipo haulingani na jumla ya kiasi kilicholipwa (Tofauti: TSh " . number_format($remainingToAllocate, 2) . ").");
                }

            } else {
                // Automatic FIFO Allocation (Oldest invoice first)
                foreach ($outstandingInvoices as $inv) {
                    if ($remainingToAllocate <= 0.00) break;

                    $invBal = (float)$inv['balance_due'];
                    $allocAmount = min($remainingToAllocate, $invBal);

                    $allocatedItems[] = [
                        'sale'   => $inv,
                        'amount' => $allocAmount
                    ];

                    $remainingToAllocate -= $allocAmount;
                }
            }

            // 5. Apply payments to each allocated invoice
            $saleUpdateStmt = $pdo->prepare("UPDATE `sales` SET paid_amount = ?, balance_due = ?, payment_status = ?, updated_at = NOW() WHERE id = ?");
            $salePaymentStmt = $pdo->prepare("
                INSERT INTO `sale_payments` (
                    payment_no, sale_id, customer_id, amount, payment_method,
                    provider, reference_no, payment_date, received_by, notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')
            ");

            $childPayments = [];
            foreach ($allocatedItems as $alloc) {
                $inv = $alloc['sale'];
                $allocAmt = $alloc['amount'];

                $newPaid = (float)$inv['paid_amount'] + $allocAmt;
                $newBal = max(0.00, (float)$inv['total_amount'] - $newPaid);
                $newStatus = ($newBal <= 0.001) ? 'paid' : 'partially_paid';

                $saleUpdateStmt->execute([$newPaid, $newBal, $newStatus, $inv['id']]);

                // Individual payment reference per invoice item linked to master payment
                $itemPayNo = self::generatePaymentNo($pdo);
                $itemNote = "Debt Collection ({$masterPaymentNo}): " . ($notes ?: "Malipo ya deni");

                $salePaymentStmt->execute([
                    $itemPayNo, $inv['id'], $customerId, $allocAmt, $paymentMethod,
                    $provider, $referenceNo, $paymentDate, $userId, $itemNote
                ]);
                $childPayments[] = [
                    'payment_no' => $itemPayNo,
                    'sale_id'    => $inv['id'],
                    'invoice_no' => $inv['invoice_no'],
                    'amount'     => $allocAmt,
                    'remaining'  => $newBal,
                    'status'     => $newStatus
                ];
            }

            // 6. Record master record in customer_payments
            $cpStmt = $pdo->prepare("
                INSERT INTO `customer_payments` (
                    payment_no, customer_id, sale_id, payment_method, provider,
                    amount, reference_no, payment_date, received_by, notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')
            ");
            $firstSaleId = !empty($allocatedItems[0]['sale']['id']) ? (int)$allocatedItems[0]['sale']['id'] : null;
            $cpStmt->execute([
                $masterPaymentNo, $customerId, $firstSaleId, $paymentMethod, $provider,
                $amount, $referenceNo, $paymentDate, $userId, $notes
            ]);
            $customerPaymentId = (int)$pdo->lastInsertId();

            // 7. Update customer's current balance
            $newCustomerBalance = max(0.00, $calculatedTotalDebt - $amount);
            $updCustStmt = $pdo->prepare("UPDATE `customers` SET current_balance = ?, updated_at = NOW() WHERE id = ?");
            $updCustStmt->execute([$newCustomerBalance, $customerId]);

            // 8. Log audit trail
            Helper::logAudit('DEBT_COLLECTED', 'debt', $customerPaymentId, null, [
                'payment_no'          => $masterPaymentNo,
                'customer_id'         => $customerId,
                'customer_name'       => $customer['name'],
                'amount'              => $amount,
                'payment_method'      => $paymentMethod,
                'provider'            => $provider,
                'reference_no'        => $referenceNo,
                'allocated_invoices'  => count($childPayments),
                'previous_total_debt' => $calculatedTotalDebt,
                'new_total_debt'      => $newCustomerBalance,
                'allocations'         => $childPayments
            ]);

            $pdo->commit();

            return [
                'success'              => true,
                'customer_payment_id'  => $customerPaymentId,
                'payment_no'           => $masterPaymentNo,
                'customer_id'          => $customerId,
                'customer_name'        => $customer['name'],
                'amount'               => $amount,
                'payment_method'       => $paymentMethod,
                'new_customer_balance' => $newCustomerBalance,
                'allocations'          => $childPayments
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Controlled Payment Reversal Workflow
     * Preserves audit history, marks payment as reversed, and recalculates invoice and customer balance
     */
    public static function reversePayment(int $paymentId, string $reason, int $userId): array {
        // Enforce RBAC permission
        if (!Auth::hasPermission('payments.reverse') && !Auth::hasRole(['super_admin', 'manager'])) {
            throw new Exception("Huna idhini ya kughairi au kubadili malipo yaliyokamilika (Action Restricted: payments.reverse).");
        }

        if (empty(trim($reason))) {
            throw new Exception("Sababu ya kughairi malipo lazima ijazwe kwa ajili ya ukaguzi (Audit trail).");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // 1. Row-lock the payment record
            $stmt = $pdo->prepare("
                SELECT sp.*, s.invoice_no, s.total_amount, s.paid_amount AS sale_paid_amount, s.balance_due AS sale_balance_due, s.sale_status
                FROM `sale_payments` sp
                JOIN `sales` s ON sp.sale_id = s.id
                WHERE sp.id = ?
                FOR UPDATE
            ");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                throw new Exception("Kumbukumbu ya malipo yenye ID #{$paymentId} haikupatikana.");
            }

            if ($payment['status'] === 'reversed') {
                throw new Exception("Malipo haya (#{$payment['payment_no']}) tayari yamekwishaghairiwa.");
            }

            $amount = (float)$payment['amount'];
            $saleId = (int)$payment['sale_id'];
            $customerId = !empty($payment['customer_id']) ? (int)$payment['customer_id'] : null;

            // 2. Mark payment as reversed
            $now = date('Y-m-d H:i:s');
            $uPay = $pdo->prepare("
                UPDATE `sale_payments`
                SET status = 'reversed', reversal_reason = ?, reversed_by = ?, reversed_at = ?
                WHERE id = ?
            ");
            $uPay->execute([$reason, $userId, $now, $paymentId]);

            // 3. Recalculate sale balance
            $currentSalePaid = (float)$payment['sale_paid_amount'];
            $saleTotal = (float)$payment['total_amount'];

            $newSalePaid = max(0.00, $currentSalePaid - $amount);
            $newSaleBalance = max(0.00, $saleTotal - $newSalePaid);

            if ($newSalePaid <= 0.001) {
                $newPaymentStatus = 'credit';
            } elseif ($newSaleBalance <= 0.001) {
                $newPaymentStatus = 'paid';
            } else {
                $newPaymentStatus = 'partially_paid';
            }

            $uSale = $pdo->prepare("
                UPDATE `sales`
                SET paid_amount = ?, balance_due = ?, payment_status = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $uSale->execute([$newSalePaid, $newSaleBalance, $newPaymentStatus, $saleId]);

            // 4. Update customer balance if linked
            if ($customerId) {
                $cStmt = $pdo->prepare("SELECT id, current_balance FROM `customers` WHERE id = ? FOR UPDATE");
                $cStmt->execute([$customerId]);
                $cust = $cStmt->fetch(PDO::FETCH_ASSOC);

                if ($cust) {
                    $newCustBal = (float)$cust['current_balance'] + $amount;
                    $uCust = $pdo->prepare("UPDATE `customers` SET current_balance = ?, updated_at = NOW() WHERE id = ?");
                    $uCust->execute([$newCustBal, $customerId]);
                }
            }

            // 5. Audit Log
            Helper::logAudit('PAYMENT_REVERSED', 'payments', $paymentId, [
                'status' => 'completed',
                'amount' => $amount
            ], [
                'payment_no'       => $payment['payment_no'],
                'status'           => 'reversed',
                'reversal_reason'  => $reason,
                'reversed_by'      => $userId,
                'reversed_at'      => $now,
                'sale_id'          => $saleId,
                'invoice_no'       => $payment['invoice_no'],
                'amount'           => $amount,
                'new_sale_balance' => $newSaleBalance,
                'new_status'       => $newPaymentStatus
            ]);

            $pdo->commit();

            return [
                'success'          => true,
                'payment_id'       => $paymentId,
                'payment_no'       => $payment['payment_no'],
                'amount'           => $amount,
                'new_sale_balance' => $newSaleBalance,
                'new_status'       => $newPaymentStatus,
                'reversal_reason'  => $reason
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Fetch payment history with filters and pagination
     */
    public static function all(array $filters = [], int $page = 1, int $perPage = 25): array {
        $pdo = Database::getConnection();
        $offset = max(0, ($page - 1) * $perPage);

        $sql = "
            SELECT sp.*, s.invoice_no, s.sale_date, s.customer_name AS sale_customer_name,
                   c.customer_code, COALESCE(c.name, s.customer_name, 'Walk-in') AS customer_name,
                   c.phone AS customer_phone,
                   u.full_name AS received_by_name, u.username AS received_by_username,
                   ru.full_name AS reversed_by_name
            FROM `sale_payments` sp
            JOIN `sales` s ON sp.sale_id = s.id
            LEFT JOIN `customers` c ON sp.customer_id = c.id
            LEFT JOIN `users` u ON sp.received_by = u.id
            LEFT JOIN `users` ru ON sp.reversed_by = ru.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (sp.payment_no LIKE ? OR sp.reference_no LIKE ? OR s.invoice_no LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(sp.payment_date) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(sp.payment_date) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['payment_method'])) {
            $sql .= " AND sp.payment_method = ?";
            $params[] = $filters['payment_method'];
        }

        if (!empty($filters['provider'])) {
            $sql .= " AND sp.provider = ?";
            $params[] = $filters['provider'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND sp.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['received_by'])) {
            $sql .= " AND sp.received_by = ?";
            $params[] = (int)$filters['received_by'];
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND (sp.customer_id = ? OR s.customer_id = ?)";
            $params[] = (int)$filters['customer_id'];
            $params[] = (int)$filters['customer_id'];
        }

        if (!empty($filters['sale_id'])) {
            $sql .= " AND sp.sale_id = ?";
            $params[] = (int)$filters['sale_id'];
        }

        $sql .= " ORDER BY sp.payment_date DESC, sp.id DESC LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count total payments matching filters
     */
    public static function count(array $filters = []): int {
        $pdo = Database::getConnection();
        $sql = "
            SELECT COUNT(*)
            FROM `sale_payments` sp
            JOIN `sales` s ON sp.sale_id = s.id
            LEFT JOIN `customers` c ON sp.customer_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['search'])) {
            $term = '%' . trim($filters['search']) . '%';
            $sql .= " AND (sp.payment_no LIKE ? OR sp.reference_no LIKE ? OR s.invoice_no LIKE ? OR c.name LIKE ? OR c.phone LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term]);
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(sp.payment_date) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(sp.payment_date) <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['payment_method'])) {
            $sql .= " AND sp.payment_method = ?";
            $params[] = $filters['payment_method'];
        }

        if (!empty($filters['provider'])) {
            $sql .= " AND sp.provider = ?";
            $params[] = $filters['provider'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND sp.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['received_by'])) {
            $sql .= " AND sp.received_by = ?";
            $params[] = (int)$filters['received_by'];
        }

        if (!empty($filters['customer_id'])) {
            $sql .= " AND (sp.customer_id = ? OR s.customer_id = ?)";
            $params[] = (int)$filters['customer_id'];
            $params[] = (int)$filters['customer_id'];
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Find payment by ID
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT sp.*, s.invoice_no, s.total_amount AS sale_total_amount, s.paid_amount AS sale_paid_amount,
                   s.balance_due AS sale_balance_due, s.sale_date, s.customer_name AS sale_customer_name,
                   c.customer_code, COALESCE(c.name, s.customer_name, 'Walk-in') AS customer_name,
                   c.phone AS customer_phone, c.address AS customer_address, c.current_balance AS customer_current_balance,
                   u.full_name AS received_by_name, u.username AS received_by_username,
                   ru.full_name AS reversed_by_name
            FROM `sale_payments` sp
            JOIN `sales` s ON sp.sale_id = s.id
            LEFT JOIN `customers` c ON sp.customer_id = c.id
            LEFT JOIN `users` u ON sp.received_by = u.id
            LEFT JOIN `users` ru ON sp.reversed_by = ru.id
            WHERE sp.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Find payment by Payment Number
     */
    public static function findByPaymentNo(string $paymentNo): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id FROM `sale_payments` WHERE payment_no = ? LIMIT 1");
        $stmt->execute([$paymentNo]);
        $id = $stmt->fetchColumn();
        return $id ? self::find((int)$id) : null;
    }

    /**
     * Daily collection summary grouped by payment methods
     */
    public static function getDailyCollection(string $date, ?int $cashierId = null): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                COUNT(*) as total_payments_count,
                COALESCE(SUM(amount), 0.00) as total_collected,
                COALESCE(SUM(CASE WHEN payment_method = 'Cash' THEN amount ELSE 0 END), 0.00) as cash_total,
                COALESCE(SUM(CASE WHEN payment_method = 'Mobile Money' THEN amount ELSE 0 END), 0.00) as mobile_money_total,
                COALESCE(SUM(CASE WHEN payment_method = 'Bank' THEN amount ELSE 0 END), 0.00) as bank_total,
                COALESCE(SUM(CASE WHEN payment_method = 'Card' THEN amount ELSE 0 END), 0.00) as card_total,
                COALESCE(SUM(CASE WHEN provider = 'M-Pesa' THEN amount ELSE 0 END), 0.00) as mpesa_total,
                COALESCE(SUM(CASE WHEN provider = 'Tigo Pesa' THEN amount ELSE 0 END), 0.00) as tigo_total,
                COALESCE(SUM(CASE WHEN provider = 'Airtel Money' THEN amount ELSE 0 END), 0.00) as airtel_total,
                COALESCE(SUM(CASE WHEN provider = 'Halopesa' THEN amount ELSE 0 END), 0.00) as halo_total
            FROM `sale_payments`
            WHERE DATE(payment_date) = ? AND status = 'completed'
        ";
        $params = [$date];

        if ($cashierId !== null) {
            $sql .= " AND received_by = ?";
            $params[] = $cashierId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        // Also get credit sales count and volume for that day from sales table
        $cSql = "
            SELECT COUNT(*) as credit_sales_count, COALESCE(SUM(balance_due), 0.00) as total_new_credit
            FROM `sales`
            WHERE DATE(sale_date) = ? AND payment_status IN ('credit', 'partially_paid') AND sale_status != 'cancelled'
        ";
        $cParams = [$date];
        if ($cashierId !== null) {
            $cSql .= " AND cashier_id = ?";
            $cParams[] = $cashierId;
        }
        $cStmt = $pdo->prepare($cSql);
        $cStmt->execute($cParams);
        $creditStats = $cStmt->fetch(PDO::FETCH_ASSOC);

        return array_merge($summary, $creditStats);
    }

    /**
     * Cashier performance collection breakdown for a given date
     */
    public static function getCashierSummary(string $date): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT u.id as cashier_id, u.full_name as cashier_name, u.username,
                   COUNT(sp.id) as payment_count,
                   COALESCE(SUM(sp.amount), 0.00) as total_collected,
                   COALESCE(SUM(CASE WHEN sp.payment_method = 'Cash' THEN sp.amount ELSE 0 END), 0.00) as cash_total,
                   COALESCE(SUM(CASE WHEN sp.payment_method = 'Mobile Money' THEN sp.amount ELSE 0 END), 0.00) as mobile_money_total,
                   COALESCE(SUM(CASE WHEN sp.payment_method = 'Bank' THEN sp.amount ELSE 0 END), 0.00) as bank_total,
                   COALESCE(SUM(CASE WHEN sp.payment_method = 'Card' THEN sp.amount ELSE 0 END), 0.00) as card_total
            FROM `users` u
            JOIN `sale_payments` sp ON u.id = sp.received_by AND DATE(sp.payment_date) = ? AND sp.status = 'completed'
            GROUP BY u.id, u.full_name, u.username
            ORDER BY total_collected DESC
        ");
        $stmt->execute([$date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Overall payment and debt KPI stats
     */
    public static function getDashboardStats(): array {
        $pdo = Database::getConnection();
        $today = date('Y-m-d');

        // Today's collections
        $daily = self::getDailyCollection($today);

        // Overall outstanding and overdue debt across all customers
        $debtStmt = $pdo->query("
            SELECT 
                COUNT(DISTINCT customer_id) as total_debtors_count,
                COALESCE(SUM(balance_due), 0.00) as total_outstanding_debt,
                COALESCE(SUM(CASE WHEN due_date IS NOT NULL AND due_date < CURRENT_DATE() THEN balance_due ELSE 0 END), 0.00) as total_overdue_debt,
                COALESCE(SUM(CASE WHEN due_date IS NOT NULL AND due_date = CURRENT_DATE() THEN balance_due ELSE 0 END), 0.00) as due_today_amount,
                COALESCE(SUM(CASE WHEN due_date IS NOT NULL AND due_date BETWEEN CURRENT_DATE() AND DATE_ADD(CURRENT_DATE(), INTERVAL 7 DAY) THEN balance_due ELSE 0 END), 0.00) as due_in_7_days_amount,
                COALESCE(SUM(CASE WHEN due_date IS NOT NULL AND due_date < DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY) THEN balance_due ELSE 0 END), 0.00) as overdue_over_30_days_amount
            FROM `sales`
            WHERE balance_due > 0 AND sale_status != 'cancelled'
        ");
        $debtStats = $debtStmt->fetch(PDO::FETCH_ASSOC);

        return [
            'today' => $daily,
            'debt'  => $debtStats
        ];
    }
}
