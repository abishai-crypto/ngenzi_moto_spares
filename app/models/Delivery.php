<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Delivery Model (Phase 16 & 17)
 * Provider-Independent Delivery Dispatch, Progression Tracking & Lifecycle Transitions
 * Dar es Salaam, Tanzania
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use PDO;
use Exception;

class Delivery {

    /**
     * Allowed lifecycle states
     */
    public const STATUS_PENDING           = 'PENDING';
    public const STATUS_PROVIDER_SELECTED = 'PROVIDER_SELECTED';
    public const STATUS_REQUESTED         = 'REQUESTED';
    public const STATUS_ACCEPTED          = 'ACCEPTED';
    public const STATUS_ASSIGNED          = 'ASSIGNED';
    public const STATUS_OUT_FOR_DELIVERY  = 'OUT_FOR_DELIVERY';
    public const STATUS_DELIVERED         = 'DELIVERED';
    public const STATUS_CANCELLED         = 'CANCELLED';
    public const STATUS_FAILED            = 'FAILED';
    public const STATUS_RETURNED          = 'RETURNED';

    // Legacy states support
    public const STATUS_PREPARING         = 'PREPARING';
    public const STATUS_READY             = 'READY';

    /**
     * Find delivery by ID or order ID
     */
    public static function findByOrder(int $orderId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `deliveries` WHERE order_id = ? LIMIT 1");
        $stmt->execute([$orderId]);
        $del = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$del) return null;

        $stmtHist = $pdo->prepare("SELECT * FROM `delivery_status_history` WHERE delivery_id = ? ORDER BY created_at ASC, id ASC");
        $stmtHist->execute([$del['id']]);
        $del['history'] = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

        return $del;
    }

    /**
     * Find delivery by delivery ID
     */
    public static function find(int $deliveryId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT d.*, oo.order_number, oo.total_amount, oo.payment_status, c.name as customer_name, c.phone as customer_phone FROM `deliveries` d JOIN `online_orders` oo ON d.order_id = oo.id JOIN `customers` c ON d.customer_id = c.id WHERE d.id = ? LIMIT 1");
        $stmt->execute([$deliveryId]);
        $del = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$del) return null;

        $stmtHist = $pdo->prepare("SELECT * FROM `delivery_status_history` WHERE delivery_id = ? ORDER BY created_at ASC, id ASC");
        $stmtHist->execute([$del['id']]);
        $del['history'] = $stmtHist->fetchAll(PDO::FETCH_ASSOC);

        return $del;
    }

    /**
     * Validate status transition lifecycle
     */
    public static function isValidTransition(string $currentStatus, string $newStatus): bool {
        $current = strtoupper(trim($currentStatus));
        $new = strtoupper(trim($newStatus));

        if ($current === $new) {
            return true;
        }

        // Terminal states cannot transition arbitrarily
        if ($current === self::STATUS_DELIVERED) {
            // Once delivered, cannot go back to pending or in transit; can only be RETURNED if returned
            return in_array($new, [self::STATUS_RETURNED]);
        }

        if ($current === self::STATUS_CANCELLED) {
            // Cancelled deliveries cannot be marked as delivered
            return false;
        }

        // Standard allowed transitions map
        $allowedTransitions = [
            self::STATUS_PENDING => [
                self::STATUS_PROVIDER_SELECTED, self::STATUS_PREPARING, self::STATUS_REQUESTED,
                self::STATUS_ACCEPTED, self::STATUS_ASSIGNED, self::STATUS_CANCELLED, self::STATUS_FAILED
            ],
            self::STATUS_PROVIDER_SELECTED => [
                self::STATUS_REQUESTED, self::STATUS_ACCEPTED, self::STATUS_ASSIGNED,
                self::STATUS_PREPARING, self::STATUS_READY, self::STATUS_OUT_FOR_DELIVERY,
                self::STATUS_CANCELLED, self::STATUS_FAILED
            ],
            self::STATUS_REQUESTED => [
                self::STATUS_ACCEPTED, self::STATUS_ASSIGNED, self::STATUS_OUT_FOR_DELIVERY,
                self::STATUS_FAILED, self::STATUS_CANCELLED
            ],
            self::STATUS_ACCEPTED => [
                self::STATUS_ASSIGNED, self::STATUS_OUT_FOR_DELIVERY, self::STATUS_CANCELLED, self::STATUS_FAILED
            ],
            self::STATUS_ASSIGNED => [
                self::STATUS_OUT_FOR_DELIVERY, self::STATUS_DELIVERED, self::STATUS_FAILED, self::STATUS_CANCELLED
            ],
            self::STATUS_PREPARING => [
                self::STATUS_READY, self::STATUS_ASSIGNED, self::STATUS_OUT_FOR_DELIVERY,
                self::STATUS_CANCELLED, self::STATUS_FAILED
            ],
            self::STATUS_READY => [
                self::STATUS_ASSIGNED, self::STATUS_OUT_FOR_DELIVERY, self::STATUS_DELIVERED,
                self::STATUS_CANCELLED, self::STATUS_FAILED
            ],
            self::STATUS_OUT_FOR_DELIVERY => [
                self::STATUS_DELIVERED, self::STATUS_FAILED, self::STATUS_RETURNED, self::STATUS_CANCELLED
            ],
            self::STATUS_FAILED => [
                self::STATUS_PROVIDER_SELECTED, self::STATUS_ASSIGNED, self::STATUS_OUT_FOR_DELIVERY,
                self::STATUS_CANCELLED, self::STATUS_RETURNED
            ],
            self::STATUS_RETURNED => []
        ];

        if (isset($allowedTransitions[$current])) {
            return in_array($new, $allowedTransitions[$current], true);
        }

        return true;
    }

    /**
     * Update delivery status with strict lifecycle validation & history logging
     */
    public static function updateStatus(int $deliveryId, string $newStatus, ?int $userId = null, string $notes = ''): bool {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT id, order_id, customer_id, delivery_status, delivery_provider FROM `deliveries` WHERE id = ?");
        $stmt->execute([$deliveryId]);
        $del = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$del) {
            throw new Exception("Rekodi ya usafirishaji haikupatikana.");
        }

        $oldStatus = strtoupper($del['delivery_status']);
        $newStatus = strtoupper(trim($newStatus));

        if ($oldStatus === $newStatus) {
            return true;
        }

        if (!self::isValidTransition($oldStatus, $newStatus)) {
            throw new Exception("Mabadiliko ya hali kutoka {$oldStatus} kwenda {$newStatus} hayaruhusiwi kulingana na muundo wa lifecycle.");
        }

        $extraSql = "";
        if ($newStatus === self::STATUS_DELIVERED) {
            $extraSql = ", delivered_at = NOW()";
        }

        $update = $pdo->prepare("UPDATE `deliveries` SET delivery_status = ?, tracking_notes = ? {$extraSql}, updated_at = NOW() WHERE id = ?");
        $update->execute([$newStatus, $notes ?: null, $deliveryId]);

        // Insert history
        $pdo->prepare("
            INSERT INTO `delivery_status_history` (delivery_id, old_status, new_status, notes, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ")->execute([$deliveryId, $oldStatus, $newStatus, $notes ?: null, $userId]);

        // Sync online_orders status
        if ($newStatus === self::STATUS_DELIVERED) {
            $pdo->prepare("UPDATE `online_orders` SET status = 'DELIVERED', delivered_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([$del['order_id']]);
        } elseif ($newStatus === self::STATUS_OUT_FOR_DELIVERY) {
            $pdo->prepare("UPDATE `online_orders` SET status = 'OUT_FOR_DELIVERY', updated_at = NOW() WHERE id = ?")->execute([$del['order_id']]);
        } elseif ($newStatus === self::STATUS_CANCELLED) {
            $pdo->prepare("UPDATE `online_orders` SET status = 'CANCELLED', cancelled_at = NOW(), updated_at = NOW() WHERE id = ?")->execute([$del['order_id']]);
        }

        Helper::logAudit('DELIVERY_STATUS_CHANGED', 'DELIVERY', $deliveryId, null, [
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'order_id'   => $del['order_id'],
            'user_id'    => $userId,
            'notes'      => $notes
        ]);

        return true;
    }

    /**
     * Assign courier, driver, or external reference (Bolt / Other)
     */
    public static function assignCourier(int $deliveryId, array $data, ?int $userId = null): bool {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("SELECT * FROM `deliveries` WHERE id = ?");
        $stmt->execute([$deliveryId]);
        $del = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$del) {
            throw new Exception("Rekodi ya usafirishaji haikupatikana.");
        }

        $fields = [];
        $params = [];

        if (isset($data['driver_name'])) {
            $fields[] = "`driver_name` = ?";
            $params[] = trim($data['driver_name']);
        }
        if (isset($data['driver_phone'])) {
            $fields[] = "`driver_phone` = ?";
            $params[] = trim($data['driver_phone']);
        }
        if (isset($data['vehicle_info'])) {
            $fields[] = "`vehicle_info` = ?";
            $params[] = trim($data['vehicle_info']);
        }
        if (isset($data['courier_name'])) {
            $fields[] = "`courier_name` = ?";
            $params[] = trim($data['courier_name']);
        }
        if (isset($data['courier_phone'])) {
            $fields[] = "`courier_phone` = ?";
            $params[] = trim($data['courier_phone']);
        }
        if (isset($data['external_reference'])) {
            $fields[] = "`external_reference` = ?";
            $params[] = trim($data['external_reference']);
        }
        if (isset($data['delivery_provider'])) {
            $fields[] = "`delivery_provider` = ?";
            $params[] = strtoupper(trim($data['delivery_provider']));
        }
        if (isset($data['courier_instructions'])) {
            $fields[] = "`courier_instructions` = ?";
            $params[] = trim($data['courier_instructions']);
        }
        if (isset($data['pickup_instructions'])) {
            $fields[] = "`pickup_instructions` = ?";
            $params[] = trim($data['pickup_instructions']);
        }
        if (isset($data['tracking_notes'])) {
            $fields[] = "`tracking_notes` = ?";
            $params[] = trim($data['tracking_notes']);
        }

        if (empty($fields)) {
            return false;
        }

        $fields[] = "`updated_at` = NOW()";
        $params[] = $deliveryId;

        $sql = "UPDATE `deliveries` SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // If newly assigned, progress status to ASSIGNED if currently PENDING or PROVIDER_SELECTED
        if (in_array($del['delivery_status'], [self::STATUS_PENDING, self::STATUS_PROVIDER_SELECTED, self::STATUS_PREPARING, self::STATUS_READY])) {
            self::updateStatus($deliveryId, self::STATUS_ASSIGNED, $userId, "Dereva / Msafirishaji amepangwa: " . ($data['driver_name'] ?? ($data['courier_name'] ?? 'Dereva')));
        }

        Helper::logAudit('DELIVERY_COURIER_ASSIGNED', 'DELIVERY', $deliveryId, null, [
            'delivery_id' => $deliveryId,
            'order_id'    => $del['order_id'],
            'assignment'  => $data
        ]);

        return true;
    }

    /**
     * List all deliveries for staff admin panel with filtering by status, provider, and search
     */
    public static function all(array $filters = [], int $page = 1, int $perPage = 25): array {
        $pdo = Database::getConnection();
        $offset = ($page - 1) * $perPage;

        $where = ["1=1"];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "d.delivery_status = ?";
            $params[] = strtoupper($filters['status']);
        }

        if (!empty($filters['provider'])) {
            $where[] = "d.delivery_provider = ?";
            $params[] = strtoupper($filters['provider']);
        }

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $where[] = "(d.delivery_number LIKE ? OR d.recipient_name LIKE ? OR d.recipient_phone LIKE ? OR oo.order_number LIKE ? OR d.external_reference LIKE ? OR d.courier_name LIKE ?)";
            $params = array_merge($params, [$term, $term, $term, $term, $term, $term]);
        }

        $whereClause = implode(" AND ", $where);
        $sql = "
            SELECT d.*, oo.order_number, oo.total_amount, oo.payment_status, c.name as customer_name, c.phone as customer_phone
            FROM `deliveries` d
            JOIN `online_orders` oo ON d.order_id = oo.id
            JOIN `customers` c ON d.customer_id = c.id
            WHERE {$whereClause}
            ORDER BY d.created_at DESC, d.id DESC
            LIMIT ? OFFSET ?
        ";

        $stmt = $pdo->prepare($sql);
        $idx = 1;
        foreach ($params as $val) {
            $stmt->bindValue($idx++, $val);
        }
        $stmt->bindValue($idx++, $perPage, PDO::PARAM_INT);
        $stmt->bindValue($idx++, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
