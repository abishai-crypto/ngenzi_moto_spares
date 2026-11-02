<?php
/**
 * NGENZI MOTO SPARE PARTS
 * CustomerAddress Model (Phase 16)
 * Handles Multiple Customer Delivery Addresses & Geolocation Support
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use PDO;
use Exception;

class CustomerAddress {
    /**
     * Get all addresses for a specific customer
     */
    public static function forCustomer(int $customerId): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM `customer_addresses` 
            WHERE customer_id = ? 
            ORDER BY is_default DESC, id DESC
        ");
        $stmt->execute([$customerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Alias for forCustomer()
     */
    public static function getByCustomerId(int $customerId): array {
        return self::forCustomer($customerId);
    }

    /**
     * Find a single address by ID with ownership verification
     */
    public static function find(int $addressId, ?int $customerId = null): ?array {
        $pdo = Database::getConnection();
        $sql = "SELECT * FROM `customer_addresses` WHERE id = ?";
        $params = [$addressId];

        if ($customerId !== null) {
            $sql .= " AND customer_id = ?";
            $params[] = $customerId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $addr = $stmt->fetch(PDO::FETCH_ASSOC);
        return $addr ?: null;
    }

    /**
     * Get default address for a customer
     */
    public static function getDefault(int $customerId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM `customer_addresses` 
            WHERE customer_id = ? AND is_default = 1 
            LIMIT 1
        ");
        $stmt->execute([$customerId]);
        $addr = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$addr) {
            // Fallback to latest
            $stmtLatest = $pdo->prepare("SELECT * FROM `customer_addresses` WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
            $stmtLatest->execute([$customerId]);
            $addr = $stmtLatest->fetch(PDO::FETCH_ASSOC);
        }
        return $addr ?: null;
    }

    /**
     * Create a new delivery address
     */
    public static function create(array $data): int {
        $pdo = Database::getConnection();

        $customerId = (int)($data['customer_id'] ?? 0);
        $label = trim($data['label'] ?? 'Nyumbani');
        $recipientName = trim($data['recipient_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $region = trim($data['region'] ?? 'Dar es Salaam');
        $district = trim($data['district'] ?? '');
        $ward = !empty($data['ward']) ? trim($data['ward']) : null;
        $street = !empty($data['street']) ? trim($data['street']) : null;
        $address = trim($data['address'] ?? '');
        $landmark = !empty($data['landmark']) ? trim($data['landmark']) : null;
        $latitude = !empty($data['latitude']) ? (float)$data['latitude'] : null;
        $longitude = !empty($data['longitude']) ? (float)$data['longitude'] : null;
        $isDefault = !empty($data['is_default']) ? 1 : 0;

        if (empty($recipientName) || empty($phone) || empty($district) || empty($address)) {
            throw new Exception("Tafadhali jaza taarifa zote muhimu za anwani (Jina, Simu, Wilaya, Mtaa/Maelezo).");
        }

        // If this is set as default or is customer's first address, clear other defaults
        $existingCount = (int)$pdo->query("SELECT COUNT(*) FROM `customer_addresses` WHERE customer_id = {$customerId}")->fetchColumn();
        if ($existingCount === 0 || $isDefault) {
            $isDefault = 1;
            $pdo->prepare("UPDATE `customer_addresses` SET is_default = 0 WHERE customer_id = ?")->execute([$customerId]);
        }

        $stmt = $pdo->prepare("
            INSERT INTO `customer_addresses` (
                customer_id, label, recipient_name, phone, region, district,
                ward, street, address, landmark, latitude, longitude, is_default, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");

        $stmt->execute([
            $customerId, $label, $recipientName, $phone, $region, $district,
            $ward, $street, $address, $landmark, $latitude, $longitude, $isDefault
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Update an existing address
     */
    public static function update(int $addressId, int $customerId, array $data): bool {
        $pdo = Database::getConnection();

        $existing = self::find($addressId, $customerId);
        if (!$existing) {
            throw new Exception("Anwani haikupatikana au huna ruhusa nayo.");
        }

        $label = trim($data['label'] ?? $existing['label']);
        $recipientName = trim($data['recipient_name'] ?? $existing['recipient_name']);
        $phone = trim($data['phone'] ?? $existing['phone']);
        $region = trim($data['region'] ?? $existing['region']);
        $district = trim($data['district'] ?? $existing['district']);
        $ward = isset($data['ward']) ? trim($data['ward']) : $existing['ward'];
        $street = isset($data['street']) ? trim($data['street']) : $existing['street'];
        $address = trim($data['address'] ?? $existing['address']);
        $landmark = isset($data['landmark']) ? trim($data['landmark']) : $existing['landmark'];
        $latitude = isset($data['latitude']) ? (float)$data['latitude'] : $existing['latitude'];
        $longitude = isset($data['longitude']) ? (float)$data['longitude'] : $existing['longitude'];
        $isDefault = isset($data['is_default']) ? ($data['is_default'] ? 1 : 0) : $existing['is_default'];

        if ($isDefault) {
            $pdo->prepare("UPDATE `customer_addresses` SET is_default = 0 WHERE customer_id = ?")->execute([$customerId]);
        }

        $stmt = $pdo->prepare("
            UPDATE `customer_addresses` SET
                label = ?, recipient_name = ?, phone = ?, region = ?,
                district = ?, ward = ?, street = ?, address = ?,
                landmark = ?, latitude = ?, longitude = ?, is_default = ?,
                updated_at = NOW()
            WHERE id = ? AND customer_id = ?
        ");

        return $stmt->execute([
            $label, $recipientName, $phone, $region,
            $district, $ward, $street, $address,
            $landmark, $latitude, $longitude, $isDefault,
            $addressId, $customerId
        ]);
    }

    /**
     * Set address as default
     */
    public static function setDefault(int $addressId, int $customerId): bool {
        $pdo = Database::getConnection();
        $pdo->prepare("UPDATE `customer_addresses` SET is_default = 0 WHERE customer_id = ?")->execute([$customerId]);
        $stmt = $pdo->prepare("UPDATE `customer_addresses` SET is_default = 1 WHERE id = ? AND customer_id = ?");
        return $stmt->execute([$addressId, $customerId]);
    }

    /**
     * Safe delete address: check if referenced by online_orders
     */
    public static function delete(int $addressId, int $customerId): bool {
        $pdo = Database::getConnection();

        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM `online_orders` WHERE delivery_address_id = ?");
        $stmtCheck->execute([$addressId]);
        $usedCount = (int)$stmtCheck->fetchColumn();

        if ($usedCount > 0) {
            // Cannot physically delete address referenced by historical orders; unlink or preserve
            // Address snapshot is already preserved in delivery_address_snapshot
            throw new Exception("Anwani hii haiwezi kufutwa kwa sababu inatumika kwenye maagizo ya awali. Unaweza kuongeza anwani mpya.");
        }

        $stmt = $pdo->prepare("DELETE FROM `customer_addresses` WHERE id = ? AND customer_id = ?");
        return $stmt->execute([$addressId, $customerId]);
    }
}
