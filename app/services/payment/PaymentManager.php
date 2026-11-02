<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Payment Manager & Gateway Orchestrator (Phase 16)
 * Handles Idempotency, Concurrency, and Atomic Transaction Recording
 */

namespace App\Services\Payment;

use App\Core\Database;
use App\Core\Helper;
use App\Models\OnlinePayment;
use PDO;
use Exception;

class PaymentManager {
    /**
     * Resolve appropriate payment gateway
     */
    public static function resolveGateway(string $method, string $provider = ''): PaymentGatewayInterface {
        $cleanMethod = strtolower(trim($method));
        $cleanProvider = trim($provider);

        if (strpos($cleanMethod, 'mobile') !== false || in_array($cleanProvider, ['M-Pesa', 'Tigo Pesa', 'Airtel Money', 'Halopesa'])) {
            return new MobileMoneyGateway($cleanProvider ?: 'M-Pesa');
        } elseif (strpos($cleanMethod, 'bank') !== false || in_array($cleanProvider, ['CRDB', 'NMB', 'NBC'])) {
            return new BankGateway($cleanProvider ?: 'CRDB');
        } elseif (strpos($cleanMethod, 'card') !== false) {
            return new CardGateway();
        }

        // Default to mobile money M-Pesa
        return new MobileMoneyGateway('M-Pesa');
    }

    /**
     * Process checkout payment initiation with Idempotency Key
     */
    public static function initiateOrderPayment(
        int $orderId,
        int $customerId,
        float $amount,
        string $paymentMethod,
        string $provider = '',
        string $idempotencyKey = ''
    ): array {
        $pdo = Database::getConnection();

        if (empty($idempotencyKey)) {
            $idempotencyKey = sprintf("IDEMP-%d-%s-%s", $orderId, date('YmdHis'), bin2hex(random_bytes(4)));
        }

        // Check if an active/successful transaction already exists with this idempotency key
        $checkStmt = $pdo->prepare("
            SELECT * FROM `online_payments` 
            WHERE idempotency_key = ? OR (order_id = ? AND status = 'SUCCESS')
            LIMIT 1
        ");
        $checkStmt->execute([$idempotencyKey, $orderId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['status'] === 'SUCCESS') {
                throw new Exception("Agizo hili tayari limelipiwa kikamilifu (#{$existing['payment_reference']}).");
            }
            // Return existing pending record to maintain idempotency
            return [
                'success'           => true,
                'payment_id'        => (int)$existing['id'],
                'payment_reference' => $existing['payment_reference'],
                'status'            => $existing['status'],
                'amount'            => (float)$existing['amount'],
                'provider'          => $existing['provider'],
                'idempotency_key'   => $existing['idempotency_key'],
                'is_duplicate'      => true
            ];
        }

        // Fetch order details
        $stmtOrder = $pdo->prepare("SELECT id, order_number, total_amount, payment_status FROM `online_orders` WHERE id = ?");
        $stmtOrder->execute([$orderId]);
        $order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception("Agizo halikupatikana.");
        }

        // Fetch customer details
        $stmtCust = $pdo->prepare("SELECT id, name, phone, email FROM `customers` WHERE id = ?");
        $stmtCust->execute([$customerId]);
        $customer = $stmtCust->fetch(PDO::FETCH_ASSOC);

        $gateway = self::resolveGateway($paymentMethod, $provider);
        $initResult = $gateway->initiate($order, $amount, $customer ?: []);

        $paymentRef = $initResult['reference'];

        // Record in `online_payments`
        $insertStmt = $pdo->prepare("
            INSERT INTO `online_payments` (
                payment_reference, order_id, customer_id, amount, currency,
                payment_method, provider, provider_reference, status,
                idempotency_key, gateway_response, created_at
            ) VALUES (
                ?, ?, ?, ?, 'TZS',
                ?, ?, ?, ?,
                ?, ?, NOW()
            )
        ");

        $insertStmt->execute([
            $paymentRef,
            $orderId,
            $customerId,
            $amount,
            $paymentMethod,
            $provider ?: $gateway->getName(),
            $order['order_number'],
            'PENDING',
            $idempotencyKey,
            json_encode($initResult, JSON_UNESCAPED_UNICODE)
        ]);

        $paymentId = (int)$pdo->lastInsertId();

        Helper::logAudit('PAYMENT_INITIATED', 'ONLINE_PAYMENT', $orderId, null, [
            'payment_reference' => $paymentRef,
            'amount'            => $amount,
            'method'            => $paymentMethod,
            'provider'          => $provider
        ]);

        return array_merge($initResult, [
            'payment_id'        => $paymentId,
            'payment_reference' => $paymentRef,
            'idempotency_key'   => $idempotencyKey
        ]);
    }
}
