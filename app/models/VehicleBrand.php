<?php
/**
 * NGENZI MOTO SPARE PARTS
 * VehicleBrand Model (Phase 4)
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use PDO;
use Exception;

class VehicleBrand {
    public static function all(?string $type = null, ?string $status = null): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT vb.*, COUNT(vm.id) as model_count
            FROM `vehicle_brands` vb
            LEFT JOIN `vehicle_models` vm ON vb.id = vm.brand_id
            WHERE 1=1
        ";
        $params = [];
        if (!empty($type)) {
            $sql .= " AND (vb.vehicle_type = ? OR vb.vehicle_type = 'Both')";
            $params[] = $type;
        }
        if (!empty($status)) {
            $sql .= " AND vb.status = ?";
            $params[] = $status;
        }
        $sql .= " GROUP BY vb.id ORDER BY vb.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `vehicle_brands` WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByName(string $name): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `vehicle_brands` WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new Exception("Jina la chapa ya chombo linahitajika.");
        }

        if (self::findByName($name)) {
            throw new Exception("Chapa ya chombo '{$name}' tayari ipo.");
        }

        $type = in_array($data['vehicle_type'] ?? '', ['Motorcycle', 'Bajaj', 'Both']) ? $data['vehicle_type'] : 'Motorcycle';
        $description = trim($data['description'] ?? '');
        $status = in_array($data['status'] ?? '', ['active', 'inactive']) ? $data['status'] : 'active';

        $stmt = $pdo->prepare("
            INSERT INTO `vehicle_brands` (`name`, `vehicle_type`, `description`, `status`)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$name, $type, $description ?: null, $status]);
        $newId = (int)$pdo->lastInsertId();

        Helper::logAudit('VEHICLE_BRAND_CREATED', 'VEHICLE_BRANDS', $newId, null, [
            'name'         => $name,
            'vehicle_type' => $type,
            'status'       => $status
        ]);

        return $newId;
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $brand = self::find($id);
        if (!$brand) {
            throw new Exception("Chapa ya chombo haikupatikana.");
        }

        $name = trim($data['name'] ?? '');
        if (empty($name)) {
            throw new Exception("Jina la chapa ya chombo linahitajika.");
        }

        $existing = self::findByName($name);
        if ($existing && (int)$existing['id'] !== $id) {
            throw new Exception("Chapa nyingine yenye jina '{$name}' tayari ipo.");
        }

        $type = in_array($data['vehicle_type'] ?? '', ['Motorcycle', 'Bajaj', 'Both']) ? $data['vehicle_type'] : $brand['vehicle_type'];
        $description = trim($data['description'] ?? '');
        $status = in_array($data['status'] ?? '', ['active', 'inactive']) ? $data['status'] : $brand['status'];

        $stmt = $pdo->prepare("
            UPDATE `vehicle_brands`
            SET `name` = ?, `vehicle_type` = ?, `description` = ?, `status` = ?
            WHERE `id` = ?
        ");
        $success = $stmt->execute([$name, $type, $description ?: null, $status, $id]);

        if ($success) {
            Helper::logAudit('VEHICLE_BRAND_UPDATED', 'VEHICLE_BRANDS', $id, $brand, [
                'name'         => $name,
                'vehicle_type' => $type,
                'status'       => $status
            ]);
        }

        return $success;
    }

    public static function toggleStatus(int $id): string {
        $pdo = Database::getConnection();
        $brand = self::find($id);
        if (!$brand) {
            throw new Exception("Chapa ya chombo haikupatikana.");
        }

        $newStatus = ($brand['status'] === 'active') ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE `vehicle_brands` SET `status` = ? WHERE `id` = ?");
        $stmt->execute([$newStatus, $id]);

        Helper::logAudit('VEHICLE_BRAND_STATUS_CHANGED', 'VEHICLE_BRANDS', $id, ['status' => $brand['status']], ['status' => $newStatus]);
        return $newStatus;
    }

    public static function count(): int {
        $pdo = Database::getConnection();
        return (int)$pdo->query("SELECT COUNT(*) FROM `vehicle_brands`")->fetchColumn();
    }
}
