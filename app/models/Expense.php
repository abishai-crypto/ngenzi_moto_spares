<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Expense Model (Phase 10)
 * Manages Operating Expenses, Number Generation, Approvals, Controlled Cancellations,
 * Categorization, Trend Analysis & Financial Impact.
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\Setting;
use App\Models\AuditLog;
use PDO;
use Exception;

require_once __DIR__ . '/Setting.php';
require_once __DIR__ . '/AuditLog.php';

class Expense {

    /**
     * Generate unique sequential expense number (e.g. EXP-000001)
     * Concurrency-safe with database existence check
     */
    public static function generateExpenseNo(?PDO $pdo = null): string {
        $pdo = $pdo ?? Database::getConnection();
        $prefix = Setting::get('expense_prefix', 'EXP-');
        if (empty($prefix)) {
            $prefix = 'EXP-';
        }

        $stmt = $pdo->query("SELECT MAX(id) FROM `expenses`");
        $maxId = (int)$stmt->fetchColumn();
        $nextNum = $maxId + 1;

        do {
            $candidate = sprintf("%s%06d", $prefix, $nextNum);
            $checkStmt = $pdo->prepare("SELECT id FROM `expenses` WHERE expense_no = ?");
            $checkStmt->execute([$candidate]);
            if (!$checkStmt->fetch()) {
                return $candidate;
            }
            $nextNum++;
        } while (true);
    }

    /**
     * Create/Record new Expense inside transaction
     */
    public static function create(array $data, int $userId): array {
        $categoryId = (int)($data['category_id'] ?? 0);
        $amount = (float)($data['amount'] ?? 0);
        $expenseDate = trim($data['expense_date'] ?? date('Y-m-d'));
        $paymentMethod = trim($data['payment_method'] ?? 'Cash');
        $provider = !empty($data['provider']) ? trim($data['provider']) : null;
        $referenceNo = !empty($data['reference_no']) ? trim($data['reference_no']) : null;
        $payee = !empty($data['payee']) ? trim($data['payee']) : null;
        $description = trim($data['description'] ?? '');
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;
        $status = in_array($data['status'] ?? 'recorded', ['draft', 'recorded', 'approved']) ? $data['status'] : 'recorded';

        // Validations
        if ($categoryId <= 0) {
            throw new Exception("Tafadhali chagua kategoria sahihi ya matumizi (Expense category is required).");
        }
        if ($amount <= 0) {
            throw new Exception("Kiasi cha matumizi lazima kiwe zaidi ya sifuri (Amount must be greater than 0).");
        }
        if (empty($description)) {
            throw new Exception("Maelezo ya matumizi yanahitajika (Description is required).");
        }
        if (empty($expenseDate) || !strtotime($expenseDate)) {
            throw new Exception("Tarehe ya matumizi si sahihi (Invalid expense date).");
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            // Verify Category Exists and is Active
            $catStmt = $pdo->prepare("SELECT id, name, status FROM `expense_categories` WHERE id = ?");
            $catStmt->execute([$categoryId]);
            $category = $catStmt->fetch(PDO::FETCH_ASSOC);

            if (!$category) {
                throw new Exception("Kategoria ya matumizi iliyochaguliwa haipo.");
            }

            // Generate unique Expense No
            $expenseNo = self::generateExpenseNo($pdo);

            // Check auto-approval if user has expenses.approve permission and status is approved
            $approvedBy = null;
            $approvedAt = null;
            if ($status === 'approved') {
                $approvedBy = $userId;
                $approvedAt = date('Y-m-d H:i:s');
            }

            $stmt = $pdo->prepare("
                INSERT INTO `expenses` (
                    `expense_no`, `category_id`, `amount`, `expense_date`, 
                    `payment_method`, `provider`, `reference_no`, `payee`, 
                    `description`, `notes`, `status`, `recorded_by`, 
                    `approved_by`, `approved_at`
                ) VALUES (
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?, ?, ?, 
                    ?, ?
                )
            ");

            $stmt->execute([
                $expenseNo, $categoryId, $amount, $expenseDate,
                $paymentMethod, $provider, $referenceNo, $payee,
                $description, $notes, $status, $userId,
                $approvedBy, $approvedAt
            ]);

            $expenseId = (int)$pdo->lastInsertId();

            // Audit Log
            AuditLog::log(
                $userId,
                'CREATE',
                'expenses',
                $expenseId,
                null,
                [
                    'expense_no' => $expenseNo,
                    'category' => $category['name'],
                    'amount' => $amount,
                    'payment_method' => $paymentMethod,
                    'expense_date' => $expenseDate,
                    'payee' => $payee,
                    'description' => $description,
                    'status' => $status
                ],
                "Umerodi matumizi mapya: {$expenseNo} - TSh " . number_format($amount, 2) . " ({$category['name']})"
            );

            $pdo->commit();

            return [
                'success' => true,
                'id' => $expenseId,
                'expense_no' => $expenseNo,
                'message' => "Matumizi namba {$expenseNo} ya TSh " . number_format($amount, 2) . " yamerekodiwa kikamilifu."
            ];

        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Update an expense (Allowed only for draft or recorded status before approval)
     */
    public static function update(int $id, array $data, int $userId): array {
        $expense = self::find($id);
        if (!$expense) {
            throw new Exception("Taarifa za matumizi hazikupatikana.");
        }

        if (in_array($expense['status'], ['approved', 'cancelled'])) {
            throw new Exception("Huwezi kubadilisha matumizi yaliyokwisha idhinishwa au kughairiwa.");
        }

        $categoryId = (int)($data['category_id'] ?? $expense['category_id']);
        $amount = (float)($data['amount'] ?? $expense['amount']);
        $expenseDate = trim($data['expense_date'] ?? $expense['expense_date']);
        $paymentMethod = trim($data['payment_method'] ?? $expense['payment_method']);
        $provider = !empty($data['provider']) ? trim($data['provider']) : null;
        $referenceNo = !empty($data['reference_no']) ? trim($data['reference_no']) : null;
        $payee = !empty($data['payee']) ? trim($data['payee']) : null;
        $description = trim($data['description'] ?? $expense['description']);
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;

        if ($categoryId <= 0) {
            throw new Exception("Tafadhali chagua kategoria sahihi ya matumizi.");
        }
        if ($amount <= 0) {
            throw new Exception("Kiasi cha matumizi lazima kiwe zaidi ya 0.");
        }
        if (empty($description)) {
            throw new Exception("Maelezo ya matumizi yanahitajika.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `expenses` SET
                `category_id` = ?, `amount` = ?, `expense_date` = ?,
                `payment_method` = ?, `provider` = ?, `reference_no` = ?,
                `payee` = ?, `description` = ?, `notes` = ?,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = ?
        ");

        $stmt->execute([
            $categoryId, $amount, $expenseDate,
            $paymentMethod, $provider, $referenceNo,
            $payee, $description, $notes,
            $id
        ]);

        AuditLog::log(
            $userId,
            'UPDATE',
            'expenses',
            $id,
            $expense,
            ['category_id' => $categoryId, 'amount' => $amount, 'description' => $description],
            "Umesasisha taarifa za matumizi {$expense['expense_no']}"
        );

        return ['success' => true, 'message' => "Matumizi {$expense['expense_no']} yamesasishwa kikamilifu."];
    }

    /**
     * Approve Expense
     */
    public static function approve(int $id, int $userId, ?string $notes = null): array {
        $expense = self::find($id);
        if (!$expense) {
            throw new Exception("Matumizi hayakupatikana.");
        }

        if ($expense['status'] === 'approved') {
            throw new Exception("Matumizi haya tayari yalishaidhinishwa.");
        }
        if ($expense['status'] === 'cancelled') {
            throw new Exception("Huwezi kuidhinisha matumizi yaliyokwisha ghairiwa.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `expenses` SET 
                `status` = 'approved',
                `approved_by` = ?,
                `approved_at` = CURRENT_TIMESTAMP,
                `notes` = CASE WHEN ? IS NOT NULL AND ? != '' THEN CONCAT(COALESCE(notes, ''), '\n[Idhini]: ', ?) ELSE notes END,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = ?
        ");
        $stmt->execute([$userId, $notes, $notes, $notes, $id]);

        AuditLog::log(
            $userId,
            'APPROVE',
            'expenses',
            $id,
            ['status' => $expense['status']],
            ['status' => 'approved', 'approved_by' => $userId, 'notes' => $notes],
            "Umeidhinisha matumizi namba {$expense['expense_no']} ya TSh " . number_format($expense['amount'], 2)
        );

        return ['success' => true, 'message' => "Matumizi {$expense['expense_no']} yameidhinishwa kikamilifu."];
    }

    /**
     * Reject Expense
     */
    public static function reject(int $id, int $userId, string $reason): array {
        $expense = self::find($id);
        if (!$expense) {
            throw new Exception("Matumizi hayakupatikana.");
        }

        if (empty(trim($reason))) {
            throw new Exception("Tafadhali weka sababu ya kukataa matumizi haya.");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `expenses` SET 
                `status` = 'rejected',
                `approved_by` = ?,
                `approved_at` = CURRENT_TIMESTAMP,
                `cancel_reason` = ?,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = ?
        ");
        $stmt->execute([$userId, $reason, $id]);

        AuditLog::log(
            $userId,
            'REJECT',
            'expenses',
            $id,
            ['status' => $expense['status']],
            ['status' => 'rejected', 'reason' => $reason],
            "Umekataa matumizi namba {$expense['expense_no']}. Sababu: {$reason}"
        );

        return ['success' => true, 'message' => "Matumizi {$expense['expense_no']} yamekataliwa."];
    }

    /**
     * Controlled Cancellation (Never delete physically from DB!)
     */
    public static function cancel(int $id, int $userId, string $reason): array {
        $expense = self::find($id);
        if (!$expense) {
            throw new Exception("Matumizi hayakupatikana.");
        }

        if ($expense['status'] === 'cancelled') {
            throw new Exception("Matumizi haya tayari yalishaghairiwa.");
        }

        if (empty(trim($reason))) {
            throw new Exception("Tafadhali weka sababu ya kughairi matumizi haya (Cancellation reason is required).");
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            UPDATE `expenses` SET 
                `status` = 'cancelled',
                `cancelled_by` = ?,
                `cancelled_at` = CURRENT_TIMESTAMP,
                `cancel_reason` = ?,
                `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = ?
        ");
        $stmt->execute([$userId, $reason, $id]);

        AuditLog::log(
            $userId,
            'CANCEL',
            'expenses',
            $id,
            ['status' => $expense['status']],
            ['status' => 'cancelled', 'cancelled_by' => $userId, 'cancel_reason' => $reason],
            "Umeghairi matumizi namba {$expense['expense_no']} ya TSh " . number_format($expense['amount'], 2) . ". Sababu: {$reason}"
        );

        return [
            'success' => true, 
            'message' => "Matumizi {$expense['expense_no']} yameghairiwa kikamilifu na kuondolewa kwenye mahesabu ya faida."
        ];
    }

    /**
     * Find expense by ID with joined relations
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                e.*,
                ec.name AS category_name,
                ec.code AS category_code,
                u_rec.full_name AS recorder_name,
                u_rec.username AS recorder_username,
                u_app.full_name AS approver_name,
                u_can.full_name AS canceller_name
            FROM `expenses` e
            JOIN `expense_categories` ec ON e.category_id = ec.id
            JOIN `users` u_rec ON e.recorded_by = u_rec.id
            LEFT JOIN `users` u_app ON e.approved_by = u_app.id
            LEFT JOIN `users` u_can ON e.cancelled_by = u_can.id
            WHERE e.id = ?
        ");
        $stmt->execute([$id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Get paginated expenses with filters
     */
    public static function all(array $filters = [], int $page = 1, int $perPage = 20): array {
        $pdo = Database::getConnection();
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                e.*,
                ec.name AS category_name,
                ec.code AS category_code,
                u_rec.full_name AS recorder_name,
                u_app.full_name AS approver_name
            FROM `expenses` e
            JOIN `expense_categories` ec ON e.category_id = ec.id
            JOIN `users` u_rec ON e.recorded_by = u_rec.id
            LEFT JOIN `users` u_app ON e.approved_by = u_app.id
            WHERE 1=1
        ";
        $countSql = "
            SELECT COUNT(*) 
            FROM `expenses` e 
            JOIN `expense_categories` ec ON e.category_id = ec.id 
            WHERE 1=1
        ";
        $sumSql = "
            SELECT COALESCE(SUM(e.amount), 0.00) 
            FROM `expenses` e 
            JOIN `expense_categories` ec ON e.category_id = ec.id 
            WHERE 1=1
        ";

        $params = [];

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $clause = " AND (e.expense_no LIKE ? OR e.description LIKE ? OR e.payee LIKE ? OR e.reference_no LIKE ? OR ec.name LIKE ?)";
            $sql .= $clause;
            $countSql .= $clause;
            $sumSql .= $clause;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['category_id'])) {
            $clause = " AND e.category_id = ?";
            $sql .= $clause;
            $countSql .= $clause;
            $sumSql .= $clause;
            $params[] = (int)$filters['category_id'];
        }

        if (!empty($filters['payment_method'])) {
            $clause = " AND e.payment_method = ?";
            $sql .= $clause;
            $countSql .= $clause;
            $sumSql .= $clause;
            $params[] = $filters['payment_method'];
        }

        if (!empty($filters['status'])) {
            $clause = " AND e.status = ?";
            $sql .= $clause;
            $countSql .= $clause;
            $sumSql .= $clause;
            $params[] = $filters['status'];
        }

        if (!empty($filters['recorded_by'])) {
            $clause = " AND e.recorded_by = ?";
            $sql .= $clause;
            $countSql .= $clause;
            $sumSql .= $clause;
            $params[] = (int)$filters['recorded_by'];
        }

        if (!empty($filters['date_from'])) {
            $clause = " AND e.expense_date >= ?";
            $sql .= $clause;
            $countSql .= $clause;
            $sumSql .= $clause;
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $clause = " AND e.expense_date <= ?";
            $sql .= $clause;
            $countSql .= $clause;
            $sumSql .= $clause;
            $params[] = $filters['date_to'];
        }

        // Get total count
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        // Get total sum for matching filter
        $sumStmt = $pdo->prepare($sumSql);
        $sumStmt->execute($params);
        $totalAmount = (float)$sumStmt->fetchColumn();

        // Get paginated data
        $sql .= " ORDER BY e.expense_date DESC, e.id DESC LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $records,
            'total' => $totalRecords,
            'total_amount' => $totalAmount,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($totalRecords / $perPage)
        ];
    }

    /**
     * Get Expenses Break Down by Category for a given Date Range
     */
    public static function getExpensesByCategory(string $startDate, string $endDate): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT 
                ec.id AS category_id,
                ec.name AS category_name,
                ec.code AS category_code,
                COUNT(e.id) AS total_count,
                COALESCE(SUM(e.amount), 0.00) AS total_amount
            FROM `expense_categories` ec
            LEFT JOIN `expenses` e ON ec.id = e.category_id 
                AND e.status IN ('recorded', 'approved')
                AND e.expense_date BETWEEN ? AND ?
            GROUP BY ec.id, ec.name, ec.code
            HAVING total_amount > 0
            ORDER BY total_amount DESC
        ");
        $stmt->execute([$startDate, $endDate]);
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grandTotal = array_sum(array_column($categories, 'total_amount'));

        foreach ($categories as &$cat) {
            $cat['percentage'] = ($grandTotal > 0) ? round(($cat['total_amount'] / $grandTotal) * 100, 2) : 0.00;
        }

        return [
            'categories' => $categories,
            'grand_total' => $grandTotal
        ];
    }

    /**
     * Get Expense Trends over time (Daily / Monthly)
     */
    public static function getExpenseTrend(string $groupBy = 'daily', string $startDate = '', string $endDate = ''): array {
        $pdo = Database::getConnection();
        
        if ($groupBy === 'monthly') {
            $dateExpr = "DATE_FORMAT(e.expense_date, '%Y-%m')";
        } else {
            $dateExpr = "DATE_FORMAT(e.expense_date, '%Y-%m-%d')";
        }

        $stmt = $pdo->prepare("
            SELECT 
                {$dateExpr} AS period_label,
                COUNT(e.id) AS transaction_count,
                COALESCE(SUM(e.amount), 0.00) AS total_amount
            FROM `expenses` e
            WHERE e.status IN ('recorded', 'approved')
              AND e.expense_date BETWEEN ? AND ?
            GROUP BY period_label
            ORDER BY period_label ASC
        ");
        $stmt->execute([$startDate, $endDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
