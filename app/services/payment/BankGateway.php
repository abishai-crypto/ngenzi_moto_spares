<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Bank Payment Gateway (CRDB, NMB, NBC Bank Transfer & Lipa Namba)
 * Phase 16 Payment Architecture
 */

namespace App\Services\Payment;

use App\Core\Env;
use App\Models\Setting;

class BankGateway implements PaymentGatewayInterface {
    protected string $bank;

    public function __construct(string $bank = 'CRDB') {
        $this->bank = $bank;
    }

    public function getName(): string {
        return "Bank Transfer ({$this->bank})";
    }

    public function getProviderStatus(): string {
        $apiEndpoint = Env::get('BANK_API_ENDPOINT');
        if (!empty($apiEndpoint)) {
            return 'READY';
        }
        return 'NOT CONFIGURED';
    }

    public function initiate(array $orderData, float $amount, array $customerData): array {
        $orderId = $orderData['id'] ?? 0;
        $orderNumber = $orderData['order_number'] ?? 'ORD-000000';
        $reference = sprintf("BNK-%s-%s-%s", strtoupper(substr($this->bank, 0, 4)), date('YmdHis'), strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6)));

        $instructions = Setting::get('bank_transfer_instructions', 'CRDB Bank | A/C: 0152899472600 | Jina: NGENZI MOTO SPARE PARTS');

        return [
            'success'          => true,
            'reference'        => $reference,
            'status'           => 'PENDING',
            'provider'         => $this->bank,
            'amount'           => $amount,
            'currency'         => 'TZS',
            'order_id'         => $orderId,
            'order_number'     => $orderNumber,
            'provider_status'  => $this->getProviderStatus(),
            'instructions'     => "Lipa kupitia: {$instructions}\nWeka kumbukumbu ya malipo: {$orderNumber}",
            'message'          => "Tafadhali kamilisha uhamisho wa benki kisha hifadhi stakabadhi/kumbukumbu ya muamala."
        ];
    }

    public function verify(string $paymentReference): array {
        return [
            'success'        => true,
            'status'         => 'PENDING',
            'provider_status'=> 'NOT CONFIGURED',
            'transaction_id' => $paymentReference,
            'paid_amount'    => 0.00,
            'message'        => 'Uthibitisho wa benki unafanywa na idara ya uhasibu baada ya fedha kuingia kwenye akaunti.'
        ];
    }

    public function handleCallback(array $payload, array $headers): array {
        return [
            'success' => false,
            'status'  => 'NOT_CONFIGURED',
            'message' => 'Bank direct integration is not configured in environment.'
        ];
    }
}
