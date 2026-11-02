<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Customer Model — Phase 7
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use PDO;
use Exception;

class Customer {

    /**
     * Generate unique customer code: CUS-000001
     */
    public static function generateCode(): string {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("SELECT COUNT(*) FROM `customers`");
        $count = (int)$stmt->fetchColumn() + 1;

        do {
            $code = sprintf("CUS-%06d", $count);
            $check = $pdo->prepare("SELECT id FROM `customers` WHERE customer_code = ? LIMIT 1");
            $check->execute([$code]);
            if (!$check->fetchColumn()) {
                return $code;
            }
            $count++;
        } while (true);
    }

    /**
     * Fetch list of customers with search, filters & pagination
     */
    public static function all(array $filters = [], int $page = 1, int $perPage = 20): array {
        $pdo = Database::getConnection();
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $where[] = "(c.name LIKE ? OR c.customer_code LIKE ? OR c.phone LIKE ? OR c.alternative_phone LIKE ? OR c.area LIKE ? OR c.email LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
        }

        if (!empty($filters['customer_type'])) {
            $where[] = "c.customer_type = ?";
            $params[] = $filters['customer_type'];
        }

        if (!empty($filters['status'])) {
            $where[] = "c.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['area'])) {
            $where[] = "c.area LIKE ?";
            $params[] = "%{$filters['area']}%";
        }

        $whereClause = implode(" AND ", $where);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT c.*,
                   COUNT(DISTINCT s.id) as total_sales_count,
                   COALESCE(SUM(s.total_amount), 0.00) as total_purchases_amount,
                   COALESCE(SUM(s.balance_due), 0.00) as total_balance_due,
                   COUNT(DISTINCT cv.id) as vehicle_count
            FROM `customers` c
            LEFT JOIN `sales` s ON c.id = s.customer_id AND s.sale_status != 'cancelled'
            LEFT JOIN `customer_vehicles` cv ON c.id = cv.customer_id AND cv.status = 'active'
            WHERE {$whereClause}
            GROUP BY c.id
            ORDER BY c.created_at DESC, c.id DESC
            LIMIT {$perPage} OFFSET {$offset}
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count customers matching filters for pagination
     */
    public static function count(array $filters = []): int {
        $pdo = Database::getConnection();
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $where[] = "(name LIKE ? OR customer_code LIKE ? OR phone LIKE ? OR alternative_phone LIKE ? OR area LIKE ? OR email LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
        }

        if (!empty($filters['customer_type'])) {
            $where[] = "customer_type = ?";
            $params[] = $filters['customer_type'];
        }

        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['area'])) {
            $where[] = "area LIKE ?";
            $params[] = "%{$filters['area']}%";
        }

        $whereClause = implode(" AND ", $where);
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `customers` WHERE {$whereClause}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Find single customer by ID
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT c.*, 
                   u1.full_name as created_by_name, u1.username as created_by_username,
                   u2.full_name as updated_by_name, u2.username as updated_by_username
            FROM `customers` c
            LEFT JOIN `users` u1 ON c.created_by = u1.id
            LEFT JOIN `users` u2 ON c.updated_by = u2.id
            WHERE c.id = ?
        ");
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        return $customer ?: null;
    }

    /**
     * Find customer by unique code
     */
    public static function findByCode(string $code): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `customers` WHERE customer_code = ? LIMIT 1");
        $stmt->execute([trim($code)]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Search customers for autocomplete / POS
     */
    public static function search(string $query, int $limit = 20): array {
        $pdo = Database::getConnection();
        $term = "%{$query}%";
        $stmt = $pdo->prepare("
            SELECT id, customer_code, name, phone, area, customer_type, status, current_balance
            FROM `customers`
            WHERE status = 'active'
              AND (name LIKE ? OR customer_code LIKE ? OR phone LIKE ? OR alternative_phone LIKE ?)
            ORDER BY name ASC
            LIMIT ?
        ");
        $stmt->bindValue(1, $term);
        $stmt->bindValue(2, $term);
        $stmt->bindValue(3, $term);
        $stmt->bindValue(4, $term);
        $stmt->bindValue(5, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new customer
     */
    public static function create(array $data): int {
        $pdo = Database::getConnection();

        $code = !empty($data['customer_code']) ? trim($data['customer_code']) : self::generateCode();
        
        // Enforce uniqueness
        $existing = self::findByCode($code);
        if ($existing) {
            throw new Exception("Kodi ya mteja '{$code}' tayari inatumika na mteja mwingine.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO `customers` (
                customer_code, name, phone, alternative_phone, email, 
                address, area, gender, customer_type, credit_limit, 
                current_balance, notes, status, created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, ?, 
                ?, ?, ?, ?, NOW()
            )
        ");

        $userId = $data['created_by'] ?? Auth::id() ?? null;
        $creditLimit = !empty($data['credit_limit']) ? (float)$data['credit_limit'] : 0.00;
        $currentBalance = !empty($data['current_balance']) ? (float)$data['current_balance'] : 0.00;
        $customerType = $data['customer_type'] ?? 'individual';
        $status = $data['status'] ?? 'active';

        $stmt->execute([
            $code,
            trim($data['name']),
            !empty($data['phone']) ? trim($data['phone']) : null,
            !empty($data['alternative_phone']) ? trim($data['alternative_phone']) : null,
            !empty($data['email']) ? trim($data['email']) : null,
            !empty($data['address']) ? trim($data['address']) : null,
            !empty($data['area']) ? trim($data['area']) : null,
            !empty($data['gender']) ? trim($data['gender']) : null,
            $customerType,
            $creditLimit,
            $currentBalance,
            !empty($data['notes']) ? trim($data['notes']) : null,
            $status,
            $userId
        ]);

        $customerId = (int)$pdo->lastInsertId();

        // Audit Log
        $stmtAudit = $pdo->prepare("
            INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
            VALUES (?, 'CUSTOMER_CREATED', 'customers', ?, ?, NOW())
        ");
        $stmtAudit->execute([
            $userId, 
            $customerId, 
            json_encode([
                'customer_code' => $code,
                'name'          => $data['name'],
                'phone'         => $data['phone'] ?? null,
                'customer_type' => $customerType
            ])
        ]);

        return $customerId;
    }

    /**
     * Update customer details
     */
    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $existing = self::find($id);
        if (!$existing) {
            throw new Exception("Mteja hakupatikana.");
        }

        $userId = $data['updated_by'] ?? Auth::id() ?? null;
        $code = !empty($data['customer_code']) ? trim($data['customer_code']) : $existing['customer_code'];

        // If code changed, check uniqueness
        if ($code !== $existing['customer_code']) {
            $check = self::findByCode($code);
            if ($check && $check['id'] !== $id) {
                throw new Exception("Kodi ya mteja '{$code}' tayari ipo kwenye mfumo.");
            }
        }

        $stmt = $pdo->prepare("
            UPDATE `customers` SET
                customer_code = ?,
                name = ?,
                phone = ?,
                alternative_phone = ?,
                email = ?,
                address = ?,
                area = ?,
                gender = ?,
                customer_type = ?,
                credit_limit = ?,
                notes = ?,
                status = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $res = $stmt->execute([
            $code,
            trim($data['name']),
            !empty($data['phone']) ? trim($data['phone']) : null,
            !empty($data['alternative_phone']) ? trim($data['alternative_phone']) : null,
            !empty($data['email']) ? trim($data['email']) : null,
            !empty($data['address']) ? trim($data['address']) : null,
            !empty($data['area']) ? trim($data['area']) : null,
            !empty($data['gender']) ? trim($data['gender']) : null,
            $data['customer_type'] ?? $existing['customer_type'],
            isset($data['credit_limit']) ? (float)$data['credit_limit'] : (float)$existing['credit_limit'],
            !empty($data['notes']) ? trim($data['notes']) : null,
            $data['status'] ?? $existing['status'],
            $userId,
            $id
        ]);

        if ($res) {
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, old_values, new_values, created_at)
                VALUES (?, 'CUSTOMER_UPDATED', 'customers', ?, ?, ?, NOW())
            ");
            $stmtAudit->execute([
                $userId,
                $id,
                json_encode(['name' => $existing['name'], 'phone' => $existing['phone'], 'status' => $existing['status']]),
                json_encode(['name' => $data['name'], 'phone' => $data['phone'] ?? null, 'status' => $data['status'] ?? $existing['status']])
            ]);
        }

        return $res;
    }

    /**
     * Change customer status (e.g. active, inactive, blocked)
     */
    public static function setStatus(int $id, string $status, ?int $userId = null): bool {
        $pdo = Database::getConnection();
        $allowed = ['active', 'inactive', 'blocked'];
        if (!in_array($status, $allowed)) {
            throw new Exception("Hali ya mteja si sahihi: {$status}");
        }

        $userId = $userId ?? Auth::id() ?? null;
        $stmt = $pdo->prepare("UPDATE `customers` SET status = ?, updated_by = ?, updated_at = NOW() WHERE id = ?");
        $res = $stmt->execute([$status, $userId, $id]);

        if ($res) {
            $action = ($status === 'inactive' || $status === 'blocked') ? 'CUSTOMER_DEACTIVATED' : 'CUSTOMER_ACTIVATED';
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
                VALUES (?, ?, 'customers', ?, ?, NOW())
            ");
            $stmtAudit->execute([$userId, $action, $id, json_encode(['status' => $status])]);
        }

        return $res;
    }

    /**
     * Aggregate statistics for a customer profile
     */
    public static function getStatistics(int $customerId): array {
        $pdo = Database::getConnection();

        // Sales aggregation
        $stmtSales = $pdo->prepare("
            SELECT COUNT(id) as total_sales_count,
                   COALESCE(SUM(total_amount), 0.00) as total_purchases_amount,
                   COALESCE(SUM(paid_amount), 0.00) as total_paid_amount,
                   COALESCE(SUM(balance_due), 0.00) as outstanding_balance,
                   MAX(sale_date) as last_purchase_date
            FROM `sales`
            WHERE customer_id = ? AND sale_status != 'cancelled'
        ");
        $stmtSales->execute([$customerId]);
        $stats = $stmtSales->fetch(PDO::FETCH_ASSOC);

        // Quantity of spare parts purchased
        $stmtQty = $pdo->prepare("
            SELECT COALESCE(SUM(si.quantity), 0) as total_items_qty
            FROM `sale_items` si
            JOIN `sales` s ON si.sale_id = s.id
            WHERE s.customer_id = ? AND s.sale_status != 'cancelled'
        ");
        $stmtQty->execute([$customerId]);
        $stats['total_items_qty'] = (int)$stmtQty->fetchColumn();

        // Total payments from customer_payments table
        $stmtPay = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0.00) as direct_payments_total
            FROM `customer_payments`
            WHERE customer_id = ?
        ");
        $stmtPay->execute([$customerId]);
        $stats['direct_payments_total'] = (float)$stmtPay->fetchColumn();

        // Vehicles count
        $stmtVeh = $pdo->prepare("SELECT COUNT(*) FROM `customer_vehicles` WHERE customer_id = ? AND status = 'active'");
        $stmtVeh->execute([$customerId]);
        $stats['vehicles_count'] = (int)$stmtVeh->fetchColumn();

        // Financial status determination
        $balance = (float)$stats['outstanding_balance'];
        if ($balance <= 0) {
            $stats['financial_status'] = 'NO BALANCE';
            $stats['financial_badge'] = 'bg-success-subtle text-success border border-success-subtle';
        } else {
            $stats['financial_status'] = 'BALANCE DUE';
            $stats['financial_badge'] = 'bg-danger-subtle text-danger border border-danger-subtle';
        }

        // Credit limit, terms & availability (Phase 9)
        $custStmt = $pdo->prepare("SELECT credit_limit, credit_allowed, payment_terms, credit_status FROM `customers` WHERE id = ?");
        $custStmt->execute([$customerId]);
        $custData = $custStmt->fetch(PDO::FETCH_ASSOC);

        $creditLimit = (float)($custData['credit_limit'] ?? 0.00);
        $stats['credit_limit'] = $creditLimit;
        $stats['credit_allowed'] = (int)($custData['credit_allowed'] ?? 1);
        $stats['payment_terms'] = $custData['payment_terms'] ?? '30 days';
        $stats['credit_status'] = $custData['credit_status'] ?? 'active';
        $stats['current_debt'] = $balance;
        $stats['available_credit'] = max(0.00, $creditLimit - $balance);

        // Overdue debt calculation
        $stmtOverdue = $pdo->prepare("
            SELECT COALESCE(SUM(balance_due), 0.00) as overdue_amount,
                   COUNT(id) as overdue_invoices_count
            FROM `sales`
            WHERE customer_id = ? AND balance_due > 0 AND due_date IS NOT NULL AND due_date < CURRENT_DATE() AND sale_status != 'cancelled'
        ");
        $stmtOverdue->execute([$customerId]);
        $overdueData = $stmtOverdue->fetch(PDO::FETCH_ASSOC);
        $stats['overdue_amount'] = (float)$overdueData['overdue_amount'];
        $stats['overdue_invoices_count'] = (int)$overdueData['overdue_invoices_count'];

        return $stats;
    }

    /**
     * Check if customer is eligible for a credit sale
     */
    public static function checkCreditEligibility(int $customerId, float $newCreditAmount, bool $hasOverridePermission = false): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT id, customer_code, name, phone, status, credit_limit, credit_allowed,
                   payment_terms, credit_status, current_balance
            FROM `customers`
            WHERE id = ?
        ");
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            return [
                'allowed'        => false,
                'needs_override' => false,
                'reason'         => "Mteja mwenye ID #{$customerId} hakupatikana."
            ];
        }

        if ($customer['status'] !== 'active') {
            return [
                'allowed'        => false,
                'needs_override' => false,
                'reason'         => "Mteja huyu amesimamishwa au hajaruhusiwa (Status: {$customer['status']}). Mauzo ya mkopo hayaruhusiwi."
            ];
        }

        if (empty($customer['credit_allowed'])) {
            return [
                'allowed'        => false,
                'needs_override' => false,
                'reason'         => "Mteja huyu hajaruhusiwa kununua kwa mkopo (Credit Not Allowed)."
            ];
        }

        if ($customer['credit_status'] === 'blocked') {
            return [
                'allowed'        => false,
                'needs_override' => false,
                'reason'         => "Akaunti ya mkopo ya mteja huyu imezuiwa (Credit Blocked)."
            ];
        }

        $creditLimit = (float)$customer['credit_limit'];
        $currentDebt = (float)$customer['current_balance'];
        $availableCredit = max(0.00, $creditLimit - $currentDebt);
        $projectedDebt = $currentDebt + $newCreditAmount;

        if (round($projectedDebt, 2) > round($creditLimit, 2)) {
            $excess = $projectedDebt - $creditLimit;
            if ($hasOverridePermission) {
                return [
                    'allowed'          => true,
                    'needs_override'   => true,
                    'is_over_limit'    => true,
                    'credit_limit'     => $creditLimit,
                    'current_debt'     => $currentDebt,
                    'available_credit' => $availableCredit,
                    'projected_debt'   => $projectedDebt,
                    'excess_amount'    => $excess,
                    'payment_terms'    => $customer['payment_terms'],
                    'reason'           => "Deni litazidi ukomo wa mkopo kwa TSh " . number_format($excess, 2) . ", lakini una idhini ya kupitisha (Override Permitted)."
                ];
            } else {
                return [
                    'allowed'          => false,
                    'needs_override'   => true,
                    'is_over_limit'    => true,
                    'credit_limit'     => $creditLimit,
                    'current_debt'     => $currentDebt,
                    'available_credit' => $availableCredit,
                    'projected_debt'   => $projectedDebt,
                    'excess_amount'    => $excess,
                    'payment_terms'    => $customer['payment_terms'],
                    'reason'           => "Deni jipya (TSh " . number_format($projectedDebt, 2) . ") linazidi ukomo wa mkopo (TSh " . number_format($creditLimit, 2) . ") kwa TSh " . number_format($excess, 2) . ". Inahitaji idhini ya Meneja."
                ];
            }
        }

        return [
            'allowed'          => true,
            'needs_override'   => false,
            'is_over_limit'    => false,
            'credit_limit'     => $creditLimit,
            'current_debt'     => $currentDebt,
            'available_credit' => $availableCredit,
            'projected_debt'   => $projectedDebt,
            'excess_amount'    => 0.00,
            'payment_terms'    => $customer['payment_terms'],
            'reason'           => 'Mteja anaruhusiwa kununua kwa mkopo.'
        ];
    }

    /**
     * Fetch all outstanding invoices for a customer with dynamic overdue calculation
     */
    public static function getOutstandingInvoices(int $customerId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.id, s.invoice_no, s.sale_date, s.due_date, s.payment_terms,
                   s.total_amount, s.paid_amount, s.balance_due, s.payment_status,
                   DATEDIFF(CURRENT_DATE(), COALESCE(s.due_date, DATE(s.sale_date))) as days_overdue,
                   CASE 
                       WHEN s.due_date IS NOT NULL AND CURRENT_DATE() > s.due_date THEN 1 
                       ELSE 0 
                   END as is_overdue
            FROM `sales` s
            WHERE s.customer_id = ? AND s.balance_due > 0 AND s.sale_status != 'cancelled'
            ORDER BY s.sale_date ASC, s.id ASC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Dynamic Debt Aging Report (Section 29 & 30)
     * Categorizes outstanding debt by days overdue:
     * - Current (not yet due)
     * - 1–7 days
     * - 8–30 days
     * - 31–60 days
     * - 61–90 days
     * - 90+ days
     */
    public static function getDebtAging(?int $customerId = null): array {
        $pdo = Database::getConnection();

        $sql = "
            SELECT 
                c.id as customer_id, c.customer_code, c.name as customer_name, c.phone as customer_phone,
                c.credit_limit, c.payment_terms,
                COALESCE(SUM(s.balance_due), 0.00) as total_debt,
                COALESCE(SUM(CASE WHEN s.due_date IS NULL OR s.due_date >= CURRENT_DATE() THEN s.balance_due ELSE 0 END), 0.00) as current_not_due,
                COALESCE(SUM(CASE WHEN s.due_date < CURRENT_DATE() AND DATEDIFF(CURRENT_DATE(), s.due_date) BETWEEN 1 AND 7 THEN s.balance_due ELSE 0 END), 0.00) as overdue_1_7,
                COALESCE(SUM(CASE WHEN s.due_date < CURRENT_DATE() AND DATEDIFF(CURRENT_DATE(), s.due_date) BETWEEN 8 AND 30 THEN s.balance_due ELSE 0 END), 0.00) as overdue_8_30,
                COALESCE(SUM(CASE WHEN s.due_date < CURRENT_DATE() AND DATEDIFF(CURRENT_DATE(), s.due_date) BETWEEN 31 AND 60 THEN s.balance_due ELSE 0 END), 0.00) as overdue_31_60,
                COALESCE(SUM(CASE WHEN s.due_date < CURRENT_DATE() AND DATEDIFF(CURRENT_DATE(), s.due_date) BETWEEN 61 AND 90 THEN s.balance_due ELSE 0 END), 0.00) as overdue_61_90,
                COALESCE(SUM(CASE WHEN s.due_date < CURRENT_DATE() AND DATEDIFF(CURRENT_DATE(), s.due_date) > 90 THEN s.balance_due ELSE 0 END), 0.00) as overdue_90_plus
            FROM `sales` s
            JOIN `customers` c ON s.customer_id = c.id
            WHERE s.balance_due > 0 AND s.sale_status != 'cancelled'
        ";
        $params = [];

        if ($customerId !== null) {
            $sql .= " AND c.id = ?";
            $params[] = $customerId;
        }

        $sql .= " GROUP BY c.id, c.customer_code, c.name, c.phone, c.credit_limit, c.payment_terms ORDER BY total_debt DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($customerId !== null) {
            return $rows[0] ?? [
                'customer_id'     => $customerId,
                'total_debt'      => 0.00,
                'current_not_due' => 0.00,
                'overdue_1_7'     => 0.00,
                'overdue_8_30'    => 0.00,
                'overdue_31_60'   => 0.00,
                'overdue_61_90'   => 0.00,
                'overdue_90_plus' => 0.00
            ];
        }

        return $rows;
    }

    /**
     * Customer Account Statement (Ledger)
     * Clearly distinguishes DEBIT (Purchases/Credit sales) and CREDIT (Payments received)
     */
    public static function getStatement(int $customerId, string $startDate, string $endDate): array {
        $pdo = Database::getConnection();
        $customer = self::find($customerId);
        if (!$customer) {
            throw new Exception("Mteja hakupatikana.");
        }

        // Calculate opening balance prior to startDate
        // Debits before startDate
        $stmtDebits = $pdo->prepare("
            SELECT COALESCE(SUM(total_amount), 0.00)
            FROM `sales`
            WHERE customer_id = ? AND DATE(sale_date) < ? AND sale_status != 'cancelled'
        ");
        $stmtDebits->execute([$customerId, $startDate]);
        $priorDebits = (float)$stmtDebits->fetchColumn();

        // Credits before startDate
        $stmtCredits = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0.00)
            FROM `sale_payments`
            WHERE customer_id = ? AND DATE(payment_date) < ? AND status = 'completed'
        ");
        $stmtCredits->execute([$customerId, $startDate]);
        $priorCredits = (float)$stmtCredits->fetchColumn();

        $openingBalance = max(0.00, $priorDebits - $priorCredits);

        // Fetch sales within date range (DEBITS)
        $salesStmt = $pdo->prepare("
            SELECT sale_date as tx_date, CONCAT('Ankara ya Mauzo #', invoice_no) as description,
                   invoice_no as reference, total_amount as debit, 0.00 as credit,
                   'debit' as tx_type, id as ref_id
            FROM `sales`
            WHERE customer_id = ? AND DATE(sale_date) BETWEEN ? AND ? AND sale_status != 'cancelled'
        ");
        $salesStmt->execute([$customerId, $startDate, $endDate]);
        $salesTx = $salesStmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch payments within date range (CREDITS)
        $payStmt = $pdo->prepare("
            SELECT sp.payment_date as tx_date, 
                   CASE 
                       WHEN sp.amount < 0 THEN CONCAT('Marejesho ya Fedha/Rejesho (', sp.payment_method, ') #', COALESCE(sp.reference_no, sp.payment_no))
                       ELSE CONCAT('Malipo (', sp.payment_method, ') #', sp.payment_no)
                   END as description,
                   sp.payment_no as reference, 
                   CASE WHEN sp.amount < 0 THEN 0.00 ELSE 0.00 END as debit,
                   CASE WHEN sp.amount < 0 THEN sp.amount ELSE sp.amount END as credit,
                   'credit' as tx_type, sp.id as ref_id
            FROM `sale_payments` sp
            WHERE sp.customer_id = ? AND DATE(sp.payment_date) BETWEEN ? AND ? AND sp.status = 'completed'
        ");
        $payStmt->execute([$customerId, $startDate, $endDate]);
        $payTx = $payStmt->fetchAll(PDO::FETCH_ASSOC);

        // Combine and sort chronologically
        $allTx = array_merge($salesTx, $payTx);
        usort($allTx, function($a, $b) {
            return strcmp($a['tx_date'], $b['tx_date']);
        });

        // Compute running balance
        $runningBal = $openingBalance;
        $totalDebits = 0.00;
        $totalCredits = 0.00;

        foreach ($allTx as &$item) {
            $d = (float)$item['debit'];
            $c = (float)$item['credit'];
            $totalDebits += $d;
            $totalCredits += $c;

            $runningBal = $runningBal + $d - $c;
            $item['balance'] = $runningBal;
        }

        return [
            'customer'        => $customer,
            'start_date'      => $startDate,
            'end_date'        => $endDate,
            'opening_balance' => $openingBalance,
            'transactions'    => $allTx,
            'total_debits'    => $totalDebits,
            'total_credits'   => $totalCredits,
            'closing_balance' => $runningBal
        ];
    }

    /**
     * Get return history for customer
     */
    public static function getCustomerReturns(int $customerId, int $limit = 50): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT r.*, s.invoice_no, u.full_name as created_by_name,
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
     * Get purchase history (sales) for customer
     */
    public static function getPurchaseHistory(int $customerId, int $limit = 50): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT s.*,
                   u.full_name as cashier_name,
                   (SELECT COUNT(*) FROM `sale_items` WHERE sale_id = s.id) as item_count
            FROM `sales` s
            LEFT JOIN `users` u ON s.cashier_id = u.id
            WHERE s.customer_id = ?
            ORDER BY s.sale_date DESC, s.id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $customerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get payment history for customer (unified across sale_payments and customer_payments)
     */
    public static function getPaymentHistory(int $customerId, int $limit = 50): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT sp.id, sp.payment_no, sp.sale_id, sp.amount, sp.payment_method, sp.provider,
                   sp.reference_no, sp.payment_date, sp.status, sp.notes,
                   u.full_name as received_by_name, s.invoice_no
            FROM `sale_payments` sp
            LEFT JOIN `users` u ON sp.received_by = u.id
            LEFT JOIN `sales` s ON sp.sale_id = s.id
            WHERE sp.customer_id = ?
            ORDER BY sp.payment_date DESC, sp.id DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $customerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Overall dashboard statistics for Customers
     */
    public static function getDashboardStats(): array {
        $pdo = Database::getConnection();

        $totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM `customers`")->fetchColumn();
        $activeCustomers = (int)$pdo->query("SELECT COUNT(*) FROM `customers` WHERE status = 'active'")->fetchColumn();
        $mechanicsCount = (int)$pdo->query("SELECT COUNT(*) FROM `mechanics` WHERE status = 'active'")->fetchColumn();
        $garagesCount = (int)$pdo->query("SELECT COUNT(*) FROM `garages` WHERE status = 'active'")->fetchColumn();

        // New customers this month
        $stmtNew = $pdo->query("
            SELECT COUNT(*) 
            FROM `customers` 
            WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
        ");
        $newThisMonth = (int)$stmtNew->fetchColumn();

        // Customers with debt
        $stmtDebt = $pdo->query("
            SELECT COUNT(DISTINCT customer_id) 
            FROM `sales` 
            WHERE balance_due > 0 AND sale_status != 'cancelled'
        ");
        $withDebtCount = (int)$stmtDebt->fetchColumn();

        // Total outstanding debt amount
        $totalDebt = (float)$pdo->query("
            SELECT COALESCE(SUM(balance_due), 0.00) 
            FROM `sales` 
            WHERE balance_due > 0 AND sale_status != 'cancelled'
        ")->fetchColumn();

        // Recent customers
        $recentCustomers = $pdo->query("
            SELECT c.*, COUNT(cv.id) as vehicle_count
            FROM `customers` c
            LEFT JOIN `customer_vehicles` cv ON c.id = cv.customer_id AND cv.status = 'active'
            GROUP BY c.id
            ORDER BY c.created_at DESC, c.id DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total_customers'   => $totalCustomers,
            'active_customers'  => $activeCustomers,
            'mechanics_count'   => $mechanicsCount,
            'garages_count'     => $garagesCount,
            'new_this_month'    => $newThisMonth,
            'with_debt_count'   => $withDebtCount,
            'total_debt_amount' => $totalDebt,
            'recent_customers'  => $recentCustomers
        ];
    }

    // =========================================================================
    // PHASE 16: ONLINE CUSTOMER REGISTRATION & PORTAL MANAGEMENT
    // =========================================================================

    /**
     * Register a new online customer account
     */
    public static function register(array $data): array {
        $pdo = Database::getConnection();

        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $confirmPassword = $data['confirm_password'] ?? '';

        if (empty($name)) {
            throw new Exception("Jina kamili linahitajika.");
        }
        if (empty($phone)) {
            throw new Exception("Namba ya simu inahitajika.");
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Barua pepe sahihi inahitajika.");
        }
        if (strlen($password) < 6) {
            throw new Exception("Nenosiri lazima liwe na herufi au tarakimu zisizopungua 6.");
        }
        if ($password !== $confirmPassword) {
            throw new Exception("Nenosiri na uthibitisho wa nenosiri havilingani.");
        }

        // Check duplicate email
        $stmtEmail = $pdo->prepare("SELECT id FROM `customers` WHERE email = ? LIMIT 1");
        $stmtEmail->execute([$email]);
        if ($stmtEmail->fetchColumn()) {
            throw new Exception("Barua pepe hii ({$email}) tayari imesajiliwa. Tafadhali ingia au tumia barua pepe nyingine.");
        }

        // Check duplicate phone
        $stmtPhone = $pdo->prepare("SELECT id FROM `customers` WHERE phone = ? LIMIT 1");
        $stmtPhone->execute([$phone]);
        if ($stmtPhone->fetchColumn()) {
            throw new Exception("Namba hii ya simu ({$phone}) tayari imesajiliwa. Tafadhali ingia kwenye akaunti yako.");
        }

        $customerCode = self::generateCode();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO `customers` (
                customer_code, name, phone, email, password,
                customer_type, is_online_registered, registration_channel,
                credit_limit, credit_allowed,
                credit_status, status, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?,
                'online', 1, 'ONLINE_STORE',
                0.00, 0,
                'active', 'active', NOW(), NOW()
            )
        ");

        $stmt->execute([
            $customerCode,
            $name,
            $phone,
            $email,
            $passwordHash
        ]);

        $customerId = (int)$pdo->lastInsertId();

        // Create welcome notification
        $pdo->prepare("
            INSERT INTO `customer_notifications` (customer_id, title, message, type, action_url, created_at)
            VALUES (?, 'Karibu Ngenzi Moto Spare Parts!', 'Akaunti yako ya duka la mtandaoni imefunguliwa kikamilifu. Karibu ununue vipuri halisi kwa bei nafuu.', 'system', 'index.php?route=shop', NOW())
        ")->execute([$customerId]);

        return [
            'id'            => $customerId,
            'customer_code' => $customerCode,
            'name'          => $name,
            'email'         => $email,
            'phone'         => $phone
        ];
    }

    /**
     * Update customer portal profile (strictly forbids changing credit or staff settings)
     */
    public static function updateOnlineProfile(int $customerId, array $data): bool {
        $pdo = Database::getConnection();

        $name = trim($data['name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));

        if (empty($name) || empty($phone) || empty($email)) {
            throw new Exception("Jina, simu na barua pepe havipaswi kuwa wazi.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Barua pepe si sahihi.");
        }

        // Check duplicate email
        $stmtEmail = $pdo->prepare("SELECT id FROM `customers` WHERE email = ? AND id != ? LIMIT 1");
        $stmtEmail->execute([$email, $customerId]);
        if ($stmtEmail->fetchColumn()) {
            throw new Exception("Barua pepe hii tayari inatumiwa na akaunti nyingine.");
        }

        // Check duplicate phone
        $stmtPhone = $pdo->prepare("SELECT id FROM `customers` WHERE phone = ? AND id != ? LIMIT 1");
        $stmtPhone->execute([$phone, $customerId]);
        if ($stmtPhone->fetchColumn()) {
            throw new Exception("Namba hii ya simu tayari inatumiwa na akaunti nyingine.");
        }

        // Password change handling
        $extraSql = "";
        $params = [$name, $phone, $email];

        if (!empty($data['new_password'])) {
            $oldPassword = $data['old_password'] ?? '';
            $newPassword = $data['new_password'];
            $confirmPassword = $data['confirm_new_password'] ?? '';

            if (strlen($newPassword) < 6) {
                throw new Exception("Nenosiri jipya lazima liwe na herufi zisizopungua 6.");
            }
            if ($newPassword !== $confirmPassword) {
                throw new Exception("Nenosiri jipya na uthibitisho havilingani.");
            }

            // Verify old password
            $currentHash = $pdo->query("SELECT password FROM `customers` WHERE id = {$customerId}")->fetchColumn();
            if (!empty($currentHash) && !password_verify($oldPassword, $currentHash)) {
                throw new Exception("Nenosiri la sasa (la zamani) si sahihi.");
            }

            $extraSql .= ", password = ?";
            $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $params[] = $customerId;

        $stmt = $pdo->prepare("
            UPDATE `customers` SET
                name = ?, phone = ?, email = ? {$extraSql}, updated_at = NOW()
            WHERE id = ?
        ");

        $res = $stmt->execute($params);

        if ($res && isset($_SESSION['customer_id']) && (int)$_SESSION['customer_id'] === $customerId) {
            $_SESSION['customer_name'] = $name;
            $_SESSION['customer_email'] = $email;
            $_SESSION['customer_phone'] = $phone;
        }

        return $res;
    }
}

