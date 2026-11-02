<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Pickup Delivery Provider (Phase 17)
 * Customer Collects Directly From Ngenzi Shop (Kariakoo)
 * Dar es Salaam, Tanzania
 */

namespace App\Services\Delivery;

use App\Models\Setting;

class PickupDeliveryProvider implements DeliveryProviderInterface {

    public function getCode(): string {
        return 'CUSTOMER_PICKUP';
    }

    public function getName(): string {
        return 'Pickup From Ngenzi Shop (Kuchukua Dukani)';
    }

    public function getDescription(): string {
        $instructions = (string)Setting::get('store_pickup_instructions', 'Chukua bidhaa zako dukani Ngenzi Moto Spare Parts, Kariakoo Dar es Salaam. Fungua kuanzia saa 2:00 asubuhi hadi 12:00 jioni.');
        return $instructions;
    }

    public function getProviderStatus(): string {
        return 'READY';
    }

    public function createDelivery(array $orderData, array $deliveryData): array {
        return [
            'success'      => true,
            'reference'    => 'PICKUP-' . date('Ymd') . '-' . bin2hex(random_bytes(3)),
            'status'       => 'PROVIDER_SELECTED',
            'fee'          => 0.00, // Absolutely free
            'message'      => 'Agizo lako litaandaliwa dukani Kariakoo. Utaarifiwa pindi litakapokuwa tayari kuchukuliwa.',
            'tracking_url' => null
        ];
    }

    public function getDeliveryStatus(string $trackingRef): array {
        return [
            'success'    => true,
            'status'     => 'PROVIDER_SELECTED',
            'raw_status' => 'PREPARING_FOR_PICKUP',
            'notes'      => 'Mzigo unaandaliwa dukani kwa ajili ya mteja kuja kuchukua.',
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    public function cancelDelivery(string $trackingRef, string $reason = ''): array {
        return [
            'success' => true,
            'message' => 'Uchukuaji dukani umesitishwa.'
        ];
    }

    public function getTrackingInformation(string $trackingRef): array {
        return [
            'available'         => true,
            'tracking_url'      => null,
            'estimated_arrival' => 'Dukani kuanzia saa 2:00 asubuhi hadi 12:00 jioni',
            'driver'            => null
        ];
    }

    public function estimateDeliveryFee(array $origin, array $destination, array $packageInfo = []): ?float {
        return 0.00;
    }
}
