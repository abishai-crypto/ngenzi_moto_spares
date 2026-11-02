<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Store Delivery Provider (Phase 17)
 * Internal Shop Fleet & Bodaboda Courier Dispatch
 * Dar es Salaam, Tanzania
 */

namespace App\Services\Delivery;

use App\Models\Setting;

class StoreDeliveryProvider implements DeliveryProviderInterface {

    public function getCode(): string {
        return 'STORE_DELIVERY';
    }

    public function getName(): string {
        return 'Store Delivery (Usafirishaji wa Ngenzi)';
    }

    public function getDescription(): string {
        return 'Usafirishaji wa duka kupitia madereva wa pikipiki wa Ngenzi moja kwa moja hadi karakana au duka lako.';
    }

    public function getProviderStatus(): string {
        return 'READY';
    }

    public function createDelivery(array $orderData, array $deliveryData): array {
        $subtotal = (float)($orderData['subtotal'] ?? 0.00);
        $freeThreshold = (float)Setting::get('free_delivery_threshold', '100000.00');
        $flatRate = (float)Setting::get('delivery_flat_rate', '5000.00');

        $fee = ($subtotal >= $freeThreshold && $subtotal > 0) ? 0.00 : $flatRate;

        return [
            'success'      => true,
            'reference'    => 'NGZ-DEL-' . date('Ymd') . '-' . bin2hex(random_bytes(3)),
            'status'       => 'PROVIDER_SELECTED',
            'fee'          => $fee,
            'message'      => 'Usafirishaji wa duka umethibitishwa. Mzigo utapangwa na dereva wa Ngenzi.',
            'tracking_url' => null
        ];
    }

    public function getDeliveryStatus(string $trackingRef): array {
        return [
            'success'    => true,
            'status'     => 'PROVIDER_SELECTED',
            'raw_status' => 'DISPATCH_PENDING',
            'notes'      => 'Mzigo unasubiri kupangwa na dereva wa duka.',
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    public function cancelDelivery(string $trackingRef, string $reason = ''): array {
        return [
            'success' => true,
            'message' => 'Usafirishaji wa duka umesitishwa.'
        ];
    }

    public function getTrackingInformation(string $trackingRef): array {
        return [
            'available'         => true,
            'tracking_url'      => null,
            'estimated_arrival' => 'Masaa 2 hadi 4 kuanzia wakati wa kuthibitisha agizo',
            'driver'            => null
        ];
    }

    public function estimateDeliveryFee(array $origin, array $destination, array $packageInfo = []): ?float {
        return (float)Setting::get('delivery_flat_rate', '5000.00');
    }
}
