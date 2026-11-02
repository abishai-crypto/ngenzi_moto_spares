<?php
/**
 * NGENZI MOTO SPARE PARTS
 * CustomerNotification Model (Phase 16)
 * In-App Notification Center
 */

namespace App\Models;

use App\Core\Database;
use PDO;

class CustomerNotification {
    /**
     * Create a customer notification
     */
    public static function create(int $customerId, string $type, string $title, string $message, ?string $actionUrl = null): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO `customer_notifications` (customer_id, title, message, type, action_url, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$customerId, $title, $message, $type, $actionUrl]);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Get all notifications for a customer
     */
    public static function forCustomer(int $customerId, int $limit = 20): array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            SELECT * FROM `customer_notifications`
            WHERE customer_id = ?
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $customerId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Count unread notifications
     */
    public static function countUnread(int $customerId): int {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM `customer_notifications` WHERE customer_id = ? AND is_read = 0");
        $stmt->execute([$customerId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Mark notification as read
     */
    public static function markAsRead(int $notificationId, int $customerId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `customer_notifications` SET is_read = 1 WHERE id = ? AND customer_id = ?");
        return $stmt->execute([$notificationId, $customerId]);
    }

    /**
     * Mark all as read
     */
    public static function markAllAsRead(int $customerId): bool {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE `customer_notifications` SET is_read = 1 WHERE customer_id = ?");
        return $stmt->execute([$customerId]);
    }
}
