<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Mobile Money Gateway (M-Pesa, Tigo Pesa, Airtel Money, Halopesa)
 * Phase 16 Payment Architecture
 */

namespace App\Services\Payment;

use App\Core\Env;
use App\Models\Setting;
use App\Core\Helper;
use Exception;

class MobileMoneyGateway implements PaymentGatewayInterface {
    protected string $provider;

    public function __construct(string $provider = 'M-Pesa') {
        $this->provider = $provider;
    }

    public function getName(): string {
        return "Mobile Money ({$this->provider})";
    }

    /**
     * Check if live API credentials exist
     */
    public function getProviderStatus(): string {
        $apiKey = Env::get('PAYMENT_API_KEY');
        $merchantId = Env::get('PAYMENT_MERCHANT_ID');
        if (!empty($apiKey) && !empty($merchantId)) {
            return 'READY';
        }
        return 'NOT CONFIGURED';
    }

    /**
     * Initiate payment: Returns instruction details & Lipa Namba or triggers push if configured
     */
    public function initiate(array $orderData, float $amount, array $customerData): array {
        $orderId = $orderData['id'] ?? 0;
        $orderNumber = $orderData['order_number'] ?? 'ORD-000000';
        $customerPhone = $customerData['phone'] ?? '';

        // Generate unique reference
        $reference = sprintf("MM-%s-%s-%s", strtoupper(substr($this->provider, 0, 3)), date('YmdHis'), strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6)));

        $lipaKey = 'lipa_namba_' . strtolower(str_replace(['-', ' '], '', $this->provider));
        $lipaNamba = Setting::get($lipaKey, Setting::get('lipa_namba_mpesa', '5522114'));

        $status = $this->getProviderStatus();

        return [
            'success'          => true,
            'reference'        => $reference,
            'status'           => 'PENDING',
            'provider'         => $this->provider,
            'amount'           => $amount,
            'currency'         => 'TZS',
            'order_id'         => $orderId,
            'order_number'     => $orderNumber,
            'provider_status'  => $status,
            'lipa_namba'       => $lipaNamba,
            'instructions'     => "1. Piga namba ya mtandao wako ({$this->provider})\n2. Chagua Lipa kwa Simu / Lipa Namba\n3. Weka Lipa Namba: {$lipaNamba}\n4. Weka Kiasi: TSh " . number_format($amount, 2) . "\n5. Weka namba ya kumbukumbu: {$orderNumber}",
            'message'          => "Agizo linasubiri uthibitisho wa malipo ya {$this->provider} kupitia Lipa Namba {$lipaNamba}."
        ];
    }

    /**
     * Verify payment status
     */
    public function verify(string $paymentReference): array {
        if ($this->getProviderStatus() === 'NOT CONFIGURED') {
            return [
                'success'        => true,
                'status'         => 'PENDING',
                'provider_status'=> 'NOT CONFIGURED',
                'transaction_id' => $paymentReference,
                'paid_amount'    => 0.00,
                'message'        => 'Seva ya moja kwa moja ya mtandao haijasanidiwa (Live gateway not configured). Uthibitisho unahitaji idhini ya mhasibu/meneja kupitia kumbukumbu ya muamala.'
            ];
        }

        // Live API integration point when credentials provided
        return [
            'success'        => false,
            'status'         => 'FAILED',
            'transaction_id' => $paymentReference,
            'paid_amount'    => 0.00,
            'message'        => 'Uthibitisho wa kiotomatiki umeshindwa.'
        ];
    }

    /**
     * Handle provider webhook / server-to-server callback
     */
    public function handleCallback(array $payload, array $headers): array {
        if ($this->getProviderStatus() === 'NOT CONFIGURED') {
            return [
                'success' => false,
                'status'  => 'NOT_CONFIGURED',
                'message' => 'Live callback listener is not configured in environment.'
            ];
        }

        // Idempotent callback processing
        $txnId = $payload['transaction_id'] ?? null;
        $orderId = (int)($payload['order_id'] ?? 0);
        $amount = (float)($payload['amount'] ?? 0.00);

        if (!$txnId || !$orderId) {
            return ['success' => false, 'status' => 'INVALID_PAYLOAD', 'message' => 'Taarifa za muamala hazijakamilika.'];
        }

        return [
            'success'        => true,
            'order_id'       => $orderId,
            'transaction_id' => $txnId,
            'amount'         => $amount,
            'status'         => 'SUCCESS',
            'message'        => 'Malipo yamethibitishwa kikamilifu.'
        ];
    }
}
