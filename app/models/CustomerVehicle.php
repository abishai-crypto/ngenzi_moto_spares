<?php
/**
 * NGENZI MOTO SPARE PARTS
 * CustomerVehicle Model — Phase 7
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Auth;
use PDO;
use Exception;

class CustomerVehicle {

    /**
     * Get all active vehicles for a specific customer
     */
    public static function getByCustomer(int $customerId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT cv.*, 
                   vm.model_name, vm.engine_capacity, vm.year_from, vm.year_to, vm.vehicle_type,
                   vb.name as brand_name
            FROM `customer_vehicles` cv
            JOIN `vehicle_models` vm ON cv.vehicle_model_id = vm.id
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            WHERE cv.customer_id = ? AND cv.status = 'active'
            ORDER BY cv.created_at DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find single customer vehicle record
     */
    public static function find(int $id): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT cv.*, 
                   c.name as customer_name, c.customer_code,
                   vm.model_name, vm.engine_capacity, vm.year_from, vm.year_to, vm.vehicle_type,
                   vb.name as brand_name
            FROM `customer_vehicles` cv
            JOIN `customers` c ON cv.customer_id = c.id
            JOIN `vehicle_models` vm ON cv.vehicle_model_id = vm.id
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            WHERE cv.id = ?
        ");
        $stmt->execute([$id]);
        $veh = $stmt->fetch(PDO::FETCH_ASSOC);
        return $veh ?: null;
    }

    /**
     * Search customer vehicle by Registration Number (e.g. T 123 ABC)
     */
    public static function findByRegistration(string $regNo): array {
        $pdo = Database::getConnection();
        $term = "%" . str_replace(' ', '', trim($regNo)) . "%";
        $stmt = $pdo->prepare("
            SELECT cv.*, 
                   c.name as customer_name, c.customer_code, c.phone as customer_phone,
                   vm.model_name, vb.name as brand_name
            FROM `customer_vehicles` cv
            JOIN `customers` c ON cv.customer_id = c.id
            JOIN `vehicle_models` vm ON cv.vehicle_model_id = vm.id
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            WHERE REPLACE(cv.registration_number, ' ', '') LIKE ?
            ORDER BY cv.created_at DESC
        ");
        $stmt->execute([$term]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Add a vehicle association to a customer
     */
    public static function addVehicle(array $data): int {
        $pdo = Database::getConnection();

        $customerId = (int)($data['customer_id'] ?? 0);
        $vehicleModelId = (int)($data['vehicle_model_id'] ?? 0);
        $regNo = !empty($data['registration_number']) ? strtoupper(trim($data['registration_number'])) : null;
        $chassisNo = !empty($data['chassis_number']) ? trim($data['chassis_number']) : null;
        $engineNo = !empty($data['engine_number']) ? trim($data['engine_number']) : null;
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;
        $userId = $data['user_id'] ?? Auth::id() ?? null;

        if ($customerId <= 0) {
            throw new Exception("Mteja lazima achaguliwe kabla ya kuunganisha chombo.");
        }
        if ($vehicleModelId <= 0) {
            throw new Exception("Muundo wa chombo (Vehicle Model) lazima uchaguliwe.");
        }

        $stmt = $pdo->prepare("
            INSERT INTO `customer_vehicles` (
                customer_id, vehicle_model_id, registration_number, 
                chassis_number, engine_number, notes, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
        ");

        $stmt->execute([$customerId, $vehicleModelId, $regNo, $chassisNo, $engineNo, $notes]);
        $vehId = (int)$pdo->lastInsertId();

        // Audit log
        $stmtAudit = $pdo->prepare("
            INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
            VALUES (?, 'CUSTOMER_VEHICLE_ADDED', 'customers', ?, ?, NOW())
        ");
        $stmtAudit->execute([
            $userId, 
            $customerId, 
            json_encode([
                'vehicle_id'          => $vehId,
                'vehicle_model_id'    => $vehicleModelId,
                'registration_number' => $regNo
            ])
        ]);

        return $vehId;
    }

    /**
     * Update customer vehicle details
     */
    public static function updateVehicle(int $id, array $data): bool {
        $pdo = Database::getConnection();
        $existing = self::find($id);
        if (!$existing) {
            throw new Exception("Taarifa za chombo cha mteja hazikupatikana.");
        }

        $regNo = !empty($data['registration_number']) ? strtoupper(trim($data['registration_number'])) : null;
        $chassisNo = !empty($data['chassis_number']) ? trim($data['chassis_number']) : null;
        $engineNo = !empty($data['engine_number']) ? trim($data['engine_number']) : null;
        $notes = !empty($data['notes']) ? trim($data['notes']) : null;
        $vehicleModelId = !empty($data['vehicle_model_id']) ? (int)$data['vehicle_model_id'] : (int)$existing['vehicle_model_id'];
        $status = $data['status'] ?? $existing['status'];
        $userId = Auth::id() ?? null;

        $stmt = $pdo->prepare("
            UPDATE `customer_vehicles` SET
                vehicle_model_id = ?,
                registration_number = ?,
                chassis_number = ?,
                engine_number = ?,
                notes = ?,
                status = ?,
                updated_at = NOW()
            WHERE id = ?
        ");

        $res = $stmt->execute([$vehicleModelId, $regNo, $chassisNo, $engineNo, $notes, $status, $id]);

        if ($res) {
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, old_values, new_values, created_at)
                VALUES (?, 'CUSTOMER_VEHICLE_UPDATED', 'customers', ?, ?, ?, NOW())
            ");
            $stmtAudit->execute([
                $userId,
                $existing['customer_id'],
                json_encode(['reg_no' => $existing['registration_number'], 'model_id' => $existing['vehicle_model_id']]),
                json_encode(['reg_no' => $regNo, 'model_id' => $vehicleModelId])
            ]);
        }

        return $res;
    }

    /**
     * Soft remove vehicle association (deactivate)
     */
    public static function removeVehicle(int $id, ?int $userId = null): bool {
        $pdo = Database::getConnection();
        $existing = self::find($id);
        if (!$existing) {
            throw new Exception("Chombo hakikupatikana.");
        }

        $userId = $userId ?? Auth::id() ?? null;
        $stmt = $pdo->prepare("UPDATE `customer_vehicles` SET status = 'inactive', updated_at = NOW() WHERE id = ?");
        $res = $stmt->execute([$id]);

        if ($res) {
            $stmtAudit = $pdo->prepare("
                INSERT INTO `audit_logs` (user_id, action, module, record_id, new_values, created_at)
                VALUES (?, 'CUSTOMER_VEHICLE_REMOVED', 'customers', ?, ?, NOW())
            ");
            $stmtAudit->execute([$userId, $existing['customer_id'], json_encode(['vehicle_id' => $id, 'reg_no' => $existing['registration_number']])]);
        }

        return $res;
    }

    /**
     * Fetch all compatible products in stock for any vehicle owned by the customer
     * (Preparation for POS & Fast Service Desk - Prompt Item 32)
     */
    public static function getCompatiblePartsForCustomer(int $customerId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT DISTINCT p.id, p.sku, p.name, p.buying_price, p.selling_price, 
                   p.current_stock, p.shelf_location,
                   c.name as category_name, b.name as brand_name, u.short_name as unit_short,
                   GROUP_CONCAT(DISTINCT CONCAT(vb.name, ' ', vm.model_name, ' (', COALESCE(cv.registration_number, 'Bila Namba'), ')') SEPARATOR ' | ') as for_vehicles
            FROM `customer_vehicles` cv
            JOIN `vehicle_models` vm ON cv.vehicle_model_id = vm.id
            JOIN `vehicle_brands` vb ON vm.brand_id = vb.id
            JOIN `product_vehicle_compatibility` pvc ON vm.id = pvc.vehicle_model_id
            JOIN `products` p ON pvc.product_id = p.id
            JOIN `categories` c ON p.category_id = c.id
            LEFT JOIN `brands` b ON p.brand_id = b.id
            JOIN `units` u ON p.unit_id = u.id
            WHERE cv.customer_id = ? AND cv.status = 'active' AND p.status = 'active'
            GROUP BY p.id
            ORDER BY p.name ASC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
