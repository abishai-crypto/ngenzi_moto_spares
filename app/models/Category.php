<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Category Model (Phase 4)
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use PDO;
use Exception;

class Category {
    public static function all(?string $status = null): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT c.*, COUNT(p.id) as product_count
            FROM `categories` c
            LEFT JOIN `products` p ON c.id = p.category_id
            WHERE 1=1
        ";
        $params = [];
        if (!empty($status)) {
            $sql .= " AND c.status = ?";
            $params[] = $status;
        }
        $sql .= " GROUP BY c.id ORDER BY c.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `categories` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByName(string $name): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `categories` WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new Exception("Jina la kategoria linahitajika.");
        }

        if (self::findByName($name)) {
            throw new Exception("Kategoria ya '{$name}' tayari ipo kwenye mfumo.");
        }

        $code = strtoupper(trim($data['code'] ?? ''));
        if (empty($code)) {
            // Auto generate 3-4 letter code from name
            $clean = preg_replace('/[^A-Za-z0-9]/', '', $name);
            $code = strtoupper(substr($clean, 0, 4));
        }

        $description = trim($data['description'] ?? '');
        $status = in_array($data['status'] ?? '', ['active', 'inactive']) ? $data['status'] : 'active';

        $stmt = $pdo->prepare("
            INSERT INTO `categories` (`name`, `code`, `description`, `status`)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $code ?: null, $description ?: null, $status]);
        $newId = (int)$pdo->lastInsertId();

        Helper::logAudit('CATEGORY_CREATED', 'CATEGORIES', $newId, null, [
            'name'   => $name,
            'code'   => $code,
            'status' => $status
        ]);

        return $newId;
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $category = self::find($id);
        if (!$category) {
            throw new Exception("Kategoria haikupatikana.");
        }

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new Exception("Jina la kategoria linahitajika.");
        }

        // Check if another category has the same name
        $existing = self::findByName($name);
        if ($existing && (int)$existing['id'] !== $id) {
            throw new Exception("Kategoria nyingine yenye jina '{$name}' tayari ipo.");
        }

        $code = strtoupper(trim($data['code'] ?? ''));
        $description = trim($data['description'] ?? '');
        $status = in_array($data['status'] ?? '', ['active', 'inactive']) ? $data['status'] : $category['status'];

        $stmt = $pdo->prepare("
            UPDATE `categories`
            SET `name` = ?, `code` = ?, `description` = ?, `status` = ?
            WHERE `id` = ?
        ");
        $success = $stmt->execute([$name, $code ?: null, $description ?: null, $status, $id]);

        if ($success) {
            Helper::logAudit('CATEGORY_UPDATED', 'CATEGORIES', $id, $category, [
                'name'   => $name,
                'code'   => $code,
                'status' => $status
            ]);
        }

        return $success;
    }

    public static function toggleStatus(int $id): string {
        $pdo = Database::getConnection();
        $category = self::find($id);
        if (!$category) {
            throw new Exception("Kategoria haikupatikana.");
        }

        $newStatus = ($category['status'] === 'active') ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE `categories` SET `status` = ? WHERE `id` = ?");
        $stmt->execute([$newStatus, $id]);

        Helper::logAudit('CATEGORY_STATUS_CHANGED', 'CATEGORIES', $id, ['status' => $category['status']], ['status' => $newStatus]);
        return $newStatus;
    }

    public static function count(): int {
        $pdo = Database::getConnection();
        return (int)$pdo->query("SELECT COUNT(*) FROM `categories`")->fetchColumn();
    }
}
