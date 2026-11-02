<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Payment Gateway Abstraction Interface (Phase 16)
 * Dar es Salaam, Tanzania
 */

namespace App\Services\Payment;

interface PaymentGatewayInterface {
    /**
     * Initiate payment transaction
     * @return array [success => bool, reference => string, status => string, message => string, data => array]
     */
    public function initiate(array $orderData, float $amount, array $customerData): array;

    /**
     * Verify payment status with provider/database
     * @return array [success => bool, status => string, transaction_id => string, paid_amount => float, message => string]
     */
    public function verify(string $paymentReference): array;

    /**
     * Handle provider webhook / server-to-server callback
     * @return array [success => bool, order_id => int, status => string, message => string]
     */
    public function handleCallback(array $payload, array $headers): array;

    /**
     * Return provider readiness status (READY, NEEDS CONFIGURATION, NOT CONFIGURED)
     */
    public function getProviderStatus(): string;

    /**
     * Get gateway display name
     */
    public function getName(): string;
}
