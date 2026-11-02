<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Other Courier Provider (Phase 17)
 * Third-Party Logistics, Regional Bus Parcels, & External Couriers
 * Dar es Salaam, Tanzania
 */

namespace App\Services\Delivery;

use App\Models\Setting;

class OtherCourierProvider implements DeliveryProviderInterface {

    public function getCode(): string {
        return 'OTHER_COURIER';
    }

    public function getName(): string {
        return 'Other Courier (Wasafirishaji Wengine / Mabasi)';
    }

    public function getDescription(): string {
        return 'Usafirishaji kupitia huduma za vifurushi, mabasi ya mikoani au wasafirishaji wengine waliopo nje ya jiji.';
    }

    public function getProviderStatus(): string {
        return 'READY';
    }

    public function createDelivery(array $orderData, array $deliveryData): array {
        $courierName = trim($deliveryData['courier_name'] ?? 'Msafirishaji Mwingine');
        $customFee = isset($deliveryData['delivery_fee']) ? (float)$deliveryData['delivery_fee'] : (float)Setting::get('delivery_flat_rate', '5000.00');

        return [
            'success'      => true,
            'reference'    => 'COURIER-' . date('Ymd') . '-' . bin2hex(random_bytes(3)),
            'status'       => 'PROVIDER_SELECTED',
            'fee'          => $customFee,
            'message'      => "Mzigo utatumwa kupitia {$courierName}. Risiti na namba ya kifurushi zitatumwa.",
            'tracking_url' => null
        ];
    }

    public function getDeliveryStatus(string $trackingRef): array {
        return [
            'success'    => true,
            'status'     => 'PROVIDER_SELECTED',
            'raw_status' => 'PENDING_COURIER_HANDOVER',
            'notes'      => 'Mzigo unaandaliwa kwa ajili ya kukabidhiwa kwa msafirishaji mteule.',
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    public function cancelDelivery(string $trackingRef, string $reason = ''): array {
        return [
            'success' => true,
            'message' => 'Usafirishaji wa msafirishaji mwingine umesitishwa.'
        ];
    }

    public function getTrackingInformation(string $trackingRef): array {
        return [
            'available'         => false,
            'tracking_url'      => null,
            'estimated_arrival' => 'Siku 1 hadi 2 kwa mikoani / masaa machache Dar es Salaam',
            'driver'            => null
        ];
    }

    public function estimateDeliveryFee(array $origin, array $destination, array $packageInfo = []): ?float {
        return (float)Setting::get('delivery_flat_rate', '5000.00');
    }
}
