<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Card Payment Gateway (Visa / Mastercard)
 * Phase 16 Payment Architecture
 */

namespace App\Services\Payment;

use App\Core\Env;

class CardGateway implements PaymentGatewayInterface {
    public function getName(): string {
        return "Card Payment (Visa / Mastercard)";
    }

    public function getProviderStatus(): string {
        $secretKey = Env::get('CARD_GATEWAY_SECRET');
        if (!empty($secretKey)) {
            return 'READY';
        }
        return 'NOT CONFIGURED';
    }

    public function initiate(array $orderData, float $amount, array $customerData): array {
        $orderId = $orderData['id'] ?? 0;
        $orderNumber = $orderData['order_number'] ?? 'ORD-000000';
        $reference = sprintf("CRD-%s-%s", date('YmdHis'), strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8)));

        return [
            'success'          => true,
            'reference'        => $reference,
            'status'           => 'PENDING',
            'provider'         => 'Card',
            'amount'           => $amount,
            'currency'         => 'TZS',
            'order_id'         => $orderId,
            'order_number'     => $orderNumber,
            'provider_status'  => $this->getProviderStatus(),
            'instructions'     => 'Malipo ya kadi ya benki yataelekezwa kwenye lango salama la benki mara tu litakapowashwa.',
            'message'          => ($this->getProviderStatus() === 'NOT CONFIGURED') 
                ? 'Lango la kadi za benki (Card Gateway) halijasanidiwa mtandaoni. Tafadhali tumia Lipa Namba au Malipo ya Moja kwa Moja.'
                : 'Unaelekezwa kwenye ukurasa salama wa kadi.'
        ];
    }

    public function verify(string $paymentReference): array {
        return [
            'success'        => false,
            'status'         => 'NOT_CONFIGURED',
            'transaction_id' => $paymentReference,
            'paid_amount'    => 0.00,
            'message'        => 'Card gateway is not configured.'
        ];
    }

    public function handleCallback(array $payload, array $headers): array {
        return [
            'success' => false,
            'status'  => 'NOT_CONFIGURED',
            'message' => 'Card callback endpoint not configured.'
        ];
    }
}
