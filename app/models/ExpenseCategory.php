<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Expense Category Model (Phase 10)
 * Manages Expense Categories, Validation, Activation/Deactivation, and Audit Trail.
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Helper;
use App\Models\AuditLog;
use PDO;
use Exception;

require_once __DIR__ . '/AuditLog.php';

class ExpenseCategory {

    /**
     * Get all active expense categories for dropdowns
     */
    public static function getActiveCategories(): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->query("
            SELECT id, name, code, description, status 
            FROM `expense_categories` 
            WHERE status = 'active' 
            ORDER BY name ASC
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all expense categories with expense count and total amount spent
     */
    public static function all(string $search = '', string $status = ''): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT 
                ec.*,
                COUNT(CASE WHEN e.status IN ('recorded', 'approved') THEN e.id END) AS total_expenses_count,
                COALESCE(SUM(CASE WHEN e.status IN ('recorded', 'approved') THEN e.amount ELSE 0 END), 0.00) AS total_amount_spent
            FROM `expense_categories` ec
            LEFT JOIN `expenses` e ON ec.id = e.category_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($search)) {
            $sql .= " AND (ec.name LIKE ? OR ec.code LIKE ? OR ec.description LIKE ?)";
            $term = "%{$search}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($status) && in_array($status, ['active', 'inactive'])) {
            $sql .= " AND ec.status = ?";
            $params[] = $status;
        }

        $sql .= " GROUP BY ec.id ORDER BY ec.name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find category by ID
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `expense_categories` WHERE id = ?");
        $stmt->execute([$id]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Create new Expense Category
     */
    public static function create(array $data, int $userId): array {
        $name = trim($data['name'] ?? '');
        $code = trim($data['code'] ?? '');
        $description = trim($data['description'] ?? '');
        $status = in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active';

        if (empty($name)) {
            throw new Exception("Jina la kategoria ya matumizi linahitajika (Category name is required).");
        }

        $pdo = Database::getConnection();

        // Check unique name
        $checkStmt = $pdo->prepare("SELECT id FROM `expense_categories` WHERE name = ?");
        $checkStmt->execute([$name]);
        if ($checkStmt->fetch()) {
            throw new Exception("Kategoria ya matumizi yenye jina '{$name}' ipo tayari.");
        }

        if (!empty($code)) {
            $checkCode = $pdo->prepare("SELECT id FROM `expense_categories` WHERE code = ?");
            $checkCode->execute([$code]);
            if ($checkCode->fetch()) {
                throw new Exception("Kodi ya kategoria '{$code}' ipo tayari.");
            }
        } else {
            $code = null;
        }

        $stmt = $pdo->prepare("
            INSERT INTO `expense_categories` (`name`, `code`, `description`, `status`)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $code, $description, $status]);
        $id = (int)$pdo->lastInsertId();

        // Audit Log
        AuditLog::log(
            $userId,
            'CREATE',
            'expense_categories',
            $id,
            null,
            ['name' => $name, 'code' => $code, 'description' => $description, 'status' => $status],
            "Umeunda kategoria mpya ya matumizi: {$name}"
        );

        return ['success' => true, 'id' => $id, 'message' => "Kategoria ya matumizi '{$name}' imeundwa kikamilifu."];
    }

    /**
     * Update Expense Category
     */
    public static function update(int $id, array $data, int $userId): array {
        $category = self::find($id);
        if (!$category) {
            throw new Exception("Kategoria ya matumizi haikupatikana.");
        }

        $name = trim($data['name'] ?? '');
        $code = trim($data['code'] ?? '');
        $description = trim($data['description'] ?? '');
        $status = in_array($data['status'] ?? 'active', ['active', 'inactive']) ? $data['status'] : 'active';

        if (empty($name)) {
            throw new Exception("Jina la kategoria linahitajika.");
        }

        $pdo = Database::getConnection();

        // Check unique name
        $checkStmt = $pdo->prepare("SELECT id FROM `expense_categories` WHERE name = ? AND id != ?");
        $checkStmt->execute([$name, $id]);
        if ($checkStmt->fetch()) {
            throw new Exception("Kategoria ya matumizi yenye jina '{$name}' ipo tayari.");
        }

        if (!empty($code)) {
            $checkCode = $pdo->prepare("SELECT id FROM `expense_categories` WHERE code = ? AND id != ?");
            $checkCode->execute([$code, $id]);
            if ($checkCode->fetch()) {
                throw new Exception("Kodi ya kategoria '{$code}' inatumiwa na kategoria nyingine.");
            }
        } else {
            $code = null;
        }

        $stmt = $pdo->prepare("
            UPDATE `expense_categories`
            SET `name` = ?, `code` = ?, `description` = ?, `status` = ?, `updated_at` = CURRENT_TIMESTAMP
            WHERE `id` = ?
        ");
        $stmt->execute([$name, $code, $description, $status, $id]);

        // Audit Log
        AuditLog::log(
            $userId,
            'UPDATE',
            'expense_categories',
            $id,
            $category,
            ['name' => $name, 'code' => $code, 'description' => $description, 'status' => $status],
            "Umebadilisha taarifa za kategoria ya matumizi: {$name}"
        );

        return ['success' => true, 'message' => "Kategoria ya matumizi '{$name}' imesasishwa kikamilifu."];
    }

    /**
     * Toggle Category Status (Active / Inactive)
     */
    public static function toggleStatus(int $id, int $userId): array {
        $category = self::find($id);
        if (!$category) {
            throw new Exception("Kategoria ya matumizi haikupatikana.");
        }

        $newStatus = ($category['status'] === 'active') ? 'inactive' : 'active';
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `expense_categories` SET `status` = ?, `updated_at` = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        AuditLog::log(
            $userId,
            'STATUS_CHANGE',
            'expense_categories',
            $id,
            ['status' => $category['status']],
            ['status' => $newStatus],
            "Imebadilisha hali ya kategoria '{$category['name']}' kuwa {$newStatus}"
        );

        return ['success' => true, 'status' => $newStatus, 'message' => "Hali ya kategoria imebadilishwa kuwa '{$newStatus}'."];
    }
}
