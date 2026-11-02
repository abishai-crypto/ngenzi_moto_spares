<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Brand Model (Phase 4)
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use PDO;
use Exception;

class Brand {
    public static function all(?string $status = null): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT b.*, COUNT(p.id) as product_count
            FROM `brands` b
            LEFT JOIN `products` p ON b.id = p.brand_id
            WHERE 1=1
        ";
        $params = [];
        if (!empty($status)) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }
        $sql .= " GROUP BY b.id ORDER BY b.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `brands` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByName(string $name): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `brands` WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new Exception("Jina la chapa/brand linahitajika.");
        }

        if (self::findByName($name)) {
            throw new Exception("Brand ya '{$name}' tayari ipo kwenye mfumo.");
        }

        $country = trim($data['country'] ?? '');
        $description = trim($data['description'] ?? '');
        $status = in_array($data['status'] ?? '', ['active', 'inactive']) ? $data['status'] : 'active';

        $stmt = $pdo->prepare("
            INSERT INTO `brands` (`name`, `country`, `description`, `status`)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $country ?: null, $description ?: null, $status]);
        $newId = (int)$pdo->lastInsertId();

        Helper::logAudit('BRAND_CREATED', 'BRANDS', $newId, null, [
            'name'    => $name,
            'country' => $country,
            'status'  => $status
        ]);

        return $newId;
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $brand = self::find($id);
        if (!$brand) {
            throw new Exception("Brand haikupatikana.");
        }

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new Exception("Jina la brand linahitajika.");
        }

        $existing = self::findByName($name);
        if ($existing && (int)$existing['id'] !== $id) {
            throw new Exception("Brand nyingine yenye jina '{$name}' tayari ipo.");
        }

        $country = trim($data['country'] ?? '');
        $description = trim($data['description'] ?? '');
        $status = in_array($data['status'] ?? '', ['active', 'inactive']) ? $data['status'] : $brand['status'];

        $stmt = $pdo->prepare("
            UPDATE `brands`
            SET `name` = ?, `country` = ?, `description` = ?, `status` = ?
            WHERE `id` = ?
        ");
        $success = $stmt->execute([$name, $country ?: null, $description ?: null, $status, $id]);

        if ($success) {
            Helper::logAudit('BRAND_UPDATED', 'BRANDS', $id, $brand, [
                'name'    => $name,
                'country' => $country,
                'status'  => $status
            ]);
        }

        return $success;
    }

    public static function toggleStatus(int $id): string {
        $pdo = Database::getConnection();
        $brand = self::find($id);
        if (!$brand) {
            throw new Exception("Brand haikupatikana.");
        }

        $newStatus = ($brand['status'] === 'active') ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE `brands` SET `status` = ? WHERE `id` = ?");
        $stmt->execute([$newStatus, $id]);

        Helper::logAudit('BRAND_STATUS_CHANGED', 'BRANDS', $id, ['status' => $brand['status']], ['status' => $newStatus]);
        return $newStatus;
    }

    public static function count(): int {
        $pdo = Database::getConnection();
        return (int)$pdo->query("SELECT COUNT(*) FROM `brands`")->fetchColumn();
    }
}
