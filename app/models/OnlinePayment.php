<?php
/**
 * NGENZI MOTO SPARE PARTS
 * OnlinePayment Model (Phase 16)
 * Idempotent Payment Recording, Receipt Generation & Order Reconciliation
 */

namespace App\Models;

use App\Core\Database;
use App\Core\Helper;
use App\Models\OnlineOrder;
use PDO;
use Exception;

class OnlinePayment {
    /**
     * Find payment by ID or Reference
     */
    public static function find(int $paymentId): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `online_payments` WHERE id = ?");
        $stmt->execute([$paymentId]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    public static function findByReference(string $reference): ?array {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM `online_payments` WHERE payment_reference = ?");
        $stmt->execute([$reference]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Mark payment as successful with idempotency verification
     */
    public static function markSuccess(
        int $paymentId,
        ?string $providerRef = null,
        ?int $verifiedByUserId = null,
        string $notes = ''
    ): array {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("SELECT * FROM `online_payments` WHERE id = ? FOR UPDATE");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment) {
                throw new Exception("Muamala wa malipo haukupatikana.");
            }

            if ($payment['status'] === 'SUCCESS') {
                // Idempotent: already marked as success
                $pdo->rollBack();
                return [
                    'success'           => true,
                    'already_completed' => true,
                    'payment'           => $payment
                ];
            }

            // Update payment record
            $updatePayment = $pdo->prepare("
                UPDATE `online_payments` SET
                    status = 'SUCCESS',
                    provider_reference = COALESCE(?, provider_reference),
                    paid_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $updatePayment->execute([$providerRef, $paymentId]);

            $orderId = (int)$payment['order_id'];

            // Update order status
            $pdo->prepare("
                UPDATE `online_orders` SET
                    payment_status = 'paid',
                    status = CASE WHEN status = 'PENDING' THEN 'CONFIRMED' ELSE status END,
                    confirmed_at = COALESCE(confirmed_at, NOW()),
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([$orderId]);

            // Add order status history
            $pdo->prepare("
                INSERT INTO `order_status_history` (order_id, old_status, new_status, changed_by_user_id, comment, created_at)
                VALUES (?, 'PENDING', 'CONFIRMED', ?, ?, NOW())
            ")->execute([
                $orderId,
                $verifiedByUserId,
                "Malipo ya TSh " . number_format($payment['amount'], 2) . " yamethibitishwa kupitia {$payment['provider']} (#{$payment['payment_reference']}). {$notes}"
            ]);

            // Automatically integrate into internal sales system
            OnlineOrder::integrateWithSales($orderId, $verifiedByUserId);

            // Notify customer
            $pdo->prepare("
                INSERT INTO `customer_notifications` (customer_id, title, message, type, action_url, created_at)
                VALUES (?, ?, ?, 'payment', ?, NOW())
            ")->execute([
                $payment['customer_id'],
                "Malipo Yamethibitishwa!",
                "Malipo yako ya TSh " . number_format($payment['amount'], 2) . " yamethibitishwa kikamilifu. Agizo lako linaandaliwa.",
                "index.php?route=customer_order_show&id={$orderId}"
            ]);

            $pdo->commit();

            Helper::logAudit('ONLINE_PAYMENT_SUCCESS', 'ONLINE_PAYMENT', $paymentId, null, [
                'order_id'          => $orderId,
                'amount'            => $payment['amount'],
                'reference'         => $payment['payment_reference'],
                'provider'          => $payment['provider']
            ]);

            return [
                'success'           => true,
                'already_completed' => false,
                'payment_id'        => $paymentId,
                'order_id'          => $orderId
            ];

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get printable receipt data
     */
    public static function getReceipt(int $paymentId, ?int $customerId = null): ?array {
        $pdo = Database::getConnection();
        $sql = "
            SELECT op.*, oo.order_number, oo.delivery_address_snapshot,
                   c.name as customer_name, c.phone as customer_phone, c.email as customer_email
            FROM `online_payments` op
            JOIN `online_orders` oo ON op.order_id = oo.id
            JOIN `customers` c ON op.customer_id = c.id
            WHERE op.id = ?
        ";
        $params = [$paymentId];

        if ($customerId !== null) {
            $sql .= " AND op.customer_id = ?";
            $params[] = $customerId;
        }

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $receipt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$receipt) {
            return null;
        }

        // Fetch order items
        $stmtItems = $pdo->prepare("SELECT * FROM `online_order_items` WHERE order_id = ?");
        $stmtItems->execute([$receipt['order_id']]);
        $receipt['items'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        return $receipt;
    }
}
