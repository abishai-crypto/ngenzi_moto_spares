<?php
/**
 * NGENZI MOTO SPARE PARTS
 * VehicleModel Model (Phase 4)
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use PDO;
use Exception;

class VehicleModel {
    public static function all(?int $brandId = null, ?string $vehicleType = null, ?string $status = null): array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT vm.*, vb.name as brand_name, COUNT(pvc.id) as compatible_parts_count
            FROM `vehicle_models` vm
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            LEFT JOIN `product_vehicle_compatibility` pvc ON vm.id = pvc.vehicle_model_id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($brandId)) {
            $sql .= " AND vm.brand_id = ?";
            $params[] = $brandId;
        }

        if (!empty($vehicleType)) {
            $sql .= " AND vm.vehicle_type = ?";
            $params[] = $vehicleType;
        }

        if (!empty($status)) {
            $sql .= " AND vm.status = ?";
            $params[] = $status;
        }

        $sql .= " GROUP BY vm.id ORDER BY vb.name ASC, vm.model_name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT vm.*, vb.name as brand_name
            FROM `vehicle_models` vm
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            WHERE vm.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function search(string $term): array {
        $pdo = Database::getConnection();
        $term = "%{$term}%";
        $stmt = $pdo->prepare("
            SELECT vm.*, vb.name as brand_name
            FROM `vehicle_models` vm
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            WHERE vm.model_name LIKE ? OR vb.name LIKE ? OR vm.engine_capacity LIKE ?
            ORDER BY vb.name ASC, vm.model_name ASC
            LIMIT 50
        ");
        $stmt->execute([$term, $term, $term]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int {
        $pdo = Database::getConnection();
        $brandId = (int)($data['brand_id'] ?? 0);
        $modelName = trim($data['model_name'] ?? '');

        if (empty($brandId) || empty($modelName)) {
            throw new Exception("Tafadhali chagua chapa ya chombo na uweke jina la muundo (Model Name).");
        }

        $brand = VehicleBrand::find($brandId);
        if (!$brand) {
            throw new Exception("Chapa ya chombo haikupatikana.");
        }

        $vehicleType = in_array($data['vehicle_type'] ?? '', ['Motorcycle', 'Bajaj']) ? $data['vehicle_type'] : 'Motorcycle';
        $yearFrom = !empty($data['year_from']) ? (int)$data['year_from'] : null;
        $yearTo = !empty($data['year_to']) ? (int)$data['year_to'] : null;
        $engineCapacity = trim($data['engine_capacity'] ?? '');
        $notes = trim($data['notes'] ?? '');
        $status = in_array($data['status'] ?? '', ['active', 'inactive']) ? $data['status'] : 'active';

        $stmt = $pdo->prepare("
            INSERT INTO `vehicle_models` 
            (`brand_id`, `model_name`, `year_from`, `year_to`, `engine_capacity`, `vehicle_type`, `notes`, `status`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $brandId,
            $modelName,
            $yearFrom,
            $yearTo,
            $engineCapacity ?: null,
            $vehicleType,
            $notes ?: null,
            $status
        ]);
        $newId = (int)$pdo->lastInsertId();

        Helper::logAudit('VEHICLE_MODEL_CREATED', 'VEHICLE_MODELS', $newId, null, [
            'brand_id'        => $brandId,
            'model_name'      => $modelName,
            'vehicle_type'    => $vehicleType,
            'engine_capacity' => $engineCapacity
        ]);

        return $newId;
    }

    public static function update(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $model = self::find($id);
        if (!$model) {
            throw new Exception("Muundo wa chombo haukupatikana.");
        }

        $brandId = (int)($data['brand_id'] ?? $model['brand_id']);
        $modelName = trim($data['model_name'] ?? $model['model_name']);

        if (empty($brandId) || empty($modelName)) {
            throw new Exception("Tafadhali chagua chapa ya chombo na uweke jina la muundo.");
        }

        $vehicleType = in_array($data['vehicle_type'] ?? '', ['Motorcycle', 'Bajaj']) ? $data['vehicle_type'] : $model['vehicle_type'];
        $yearFrom = !empty($data['year_from']) ? (int)$data['year_from'] : null;
        $yearTo = !empty($data['year_to']) ? (int)$data['year_to'] : null;
        $engineCapacity = trim($data['engine_capacity'] ?? '');
        $notes = trim($data['notes'] ?? '');
        $status = in_array($data['status'] ?? '', ['active', 'inactive']) ? $data['status'] : $model['status'];

        $stmt = $pdo->prepare("
            UPDATE `vehicle_models`
            SET `brand_id` = ?, `model_name` = ?, `year_from` = ?, `year_to` = ?, 
                `engine_capacity` = ?, `vehicle_type` = ?, `notes` = ?, `status` = ?
            WHERE `id` = ?
        ");
        $success = $stmt->execute([
            $brandId,
            $modelName,
            $yearFrom,
            $yearTo,
            $engineCapacity ?: null,
            $vehicleType,
            $notes ?: null,
            $status,
            $id
        ]);

        if ($success) {
            Helper::logAudit('VEHICLE_MODEL_UPDATED', 'VEHICLE_MODELS', $id, $model, [
                'brand_id'        => $brandId,
                'model_name'      => $modelName,
                'vehicle_type'    => $vehicleType,
                'engine_capacity' => $engineCapacity
            ]);
        }

        return $success;
    }

    public static function toggleStatus(int $id): string {
        $pdo = Database::getConnection();
        $model = self::find($id);
        if (!$model) {
            throw new Exception("Muundo wa chombo haukupatikana.");
        }

        $newStatus = ($model['status'] === 'active') ? 'inactive' : 'active';
        $stmt = $pdo->prepare("UPDATE `vehicle_models` SET `status` = ? WHERE `id` = ?");
        $stmt->execute([$newStatus, $id]);

        Helper::logAudit('VEHICLE_MODEL_STATUS_CHANGED', 'VEHICLE_MODELS', $id, ['status' => $model['status']], ['status' => $newStatus]);
        return $newStatus;
    }

    public static function count(): int {
        $pdo = Database::getConnection();
        return (int)$pdo->query("SELECT COUNT(*) FROM `vehicle_models`")->fetchColumn();
    }
}
