<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Delivery Provider Abstraction Interface (Phase 17)
 * Provider-Independent Delivery Architecture
 * Dar es Salaam, Tanzania
 */

namespace App\Services\Delivery;

interface DeliveryProviderInterface {
    /**
     * Create / Dispatch delivery request with the provider
     * @param array $orderData Order details
     * @param array $deliveryData Delivery parameters (recipient, address, phone, notes, etc.)
     * @return array [success => bool, reference => string, status => string, fee => float, message => string, tracking_url => string|null]
     */
    public function createDelivery(array $orderData, array $deliveryData): array;

    /**
     * Query delivery status from provider
     * @param string $trackingRef External or internal tracking reference
     * @return array [success => bool, status => string, raw_status => string, notes => string, updated_at => string]
     */
    public function getDeliveryStatus(string $trackingRef): array;

    /**
     * Cancel an active delivery
     * @param string $trackingRef
     * @param string $reason
     * @return array [success => bool, message => string]
     */
    public function cancelDelivery(string $trackingRef, string $reason = ''): array;

    /**
     * Get real-time tracking information
     * @param string $trackingRef
     * @return array [available => bool, tracking_url => string|null, estimated_arrival => string|null, driver => array|null]
     */
    public function getTrackingInformation(string $trackingRef): array;

    /**
     * Estimate delivery fee based on locations
     * @param array $origin
     * @param array $destination
     * @param array $packageInfo
     * @return float|null Estimated fee in TSh, or null if cannot estimate
     */
    public function estimateDeliveryFee(array $origin, array $destination, array $packageInfo = []): ?float;

    /**
     * Readiness state: READY, NOT CONFIGURED, NEEDS CONFIGURATION
     */
    public function getProviderStatus(): string;

    /**
     * Unique provider code: BOLT, STORE_DELIVERY, CUSTOMER_COURIER, OTHER_COURIER, CUSTOMER_PICKUP
     */
    public function getCode(): string;

    /**
     * Human-friendly name
     */
    public function getName(): string;

    /**
     * Short Swahili / English description for checkout UI
     */
    public function getDescription(): string;
}
