<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Notification Architecture (Phase 17)
 * In-App, Email, SMS & WhatsApp Dispatch Orchestrator
 * Dar es Salaam, Tanzania
 */

namespace App\Services\Notification;

use App\Core\Database;
use App\Core\Helper;
use App\Models\Setting;
use PDO;
use Exception;

class NotificationManager {

    /**
     * Send order notification across enabled channels
     */
    public static function sendOrderNotification(array $order, string $eventType = 'ORDER_PLACED'): array {
        $customerId = (int)($order['customer_id'] ?? 0);
        $orderNumber = $order['order_number'] ?? ('ORD-' . ($order['id'] ?? 0));
        $deliveryMethod = $order['delivery_provider'] ?? ($order['delivery_method'] ?? 'STORE_DELIVERY');
        $deliveryStatus = $order['delivery_status'] ?? 'PENDING';
        $paymentStatus = $order['payment_status'] ?? 'unpaid';
        $totalAmount = (float)($order['total_amount'] ?? 0.00);
        $supportPhone = (string)Setting::get('customer_support_phone', '+255 754 123 456');

        $title = "Agizo #{$orderNumber} Limepokelewa";
        $message = sprintf(
            "Habari! Agizo lako #%s lenye thamani ya TSh %s limepokelewa. Njia ya Usafirishaji: %s. Hali ya Malipo: %s. Kwa msaada piga: %s.",
            $orderNumber,
            number_format($totalAmount, 2),
            $deliveryMethod,
            strtoupper($paymentStatus),
            $supportPhone
        );

        $actionUrl = "index.php?route=customer_order_show&id=" . ($order['id'] ?? 0);

        // 1. In-App Notification (Always Active & Persisted)
        $inAppSuccess = false;
        if ($customerId > 0) {
            $inAppSuccess = self::sendInApp($customerId, $title, $message, 'order', $actionUrl);
        }

        // 2. Multi-channel External Status Tracking (Do not fake external APIs)
        $emailStatus = self::sendEmail($order, $title, $message);
        $smsStatus = self::sendSms($order, $message);
        $whatsappStatus = self::sendWhatsApp($order, $message);

        return [
            'in_app'   => $inAppSuccess ? 'SENT' : 'FAILED',
            'email'    => $emailStatus,
            'sms'      => $smsStatus,
            'whatsapp' => $whatsappStatus,
            'title'    => $title,
            'message'  => $message
        ];
    }

    /**
     * In-App notification recorded in `customer_notifications`
     */
    public static function sendInApp(int $customerId, string $title, string $message, string $type = 'system', ?string $actionUrl = null): bool {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                INSERT INTO `customer_notifications` (customer_id, title, message, type, action_url, is_read, created_at)
                VALUES (?, ?, ?, ?, ?, 0, NOW())
            ");
            return $stmt->execute([$customerId, $title, $message, $type, $actionUrl]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Email notification check
     */
    public static function sendEmail(array $order, string $subject, string $body): string {
        $enabled = (string)Setting::get('notification_email_enabled', '0');
        if ($enabled !== '1') {
            return 'NOT CONFIGURED';
        }
        // If credentials/SMTP exist in future, dispatch here
        return 'NOT CONFIGURED';
    }

    /**
     * SMS notification check
     */
    public static function sendSms(array $order, string $message): string {
        $enabled = (string)Setting::get('notification_sms_enabled', '0');
        if ($enabled !== '1') {
            return 'NOT CONFIGURED';
        }
        return 'NOT CONFIGURED';
    }

    /**
     * WhatsApp notification check
     */
    public static function sendWhatsApp(array $order, string $message): string {
        $enabled = (string)Setting::get('notification_whatsapp_enabled', '0');
        if ($enabled !== '1') {
            return 'NOT CONFIGURED';
        }
        return 'NOT CONFIGURED';
    }
}
