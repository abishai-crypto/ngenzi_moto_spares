<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Customer Courier Provider (Phase 17)
 * Customer Arranges Their Own Bodaboda / Courier to Pick Up at Shop
 * Dar es Salaam, Tanzania
 */

namespace App\Services\Delivery;

class CustomerCourierProvider implements DeliveryProviderInterface {

    public function getCode(): string {
        return 'CUSTOMER_COURIER';
    }

    public function getName(): string {
        return "My Own Courier (Msafirishaji Wangu)";
    }

    public function getDescription(): string {
        return 'Utamtuma msafirishaji/bodaboda wako binafsi kuchukua mzigo dukani kwetu mara utakapokuwa tayari.';
    }

    public function getProviderStatus(): string {
        return 'READY';
    }

    public function createDelivery(array $orderData, array $deliveryData): array {
        $courierName = trim($deliveryData['courier_name'] ?? '');
        $courierPhone = trim($deliveryData['courier_phone'] ?? '');

        return [
            'success'      => true,
            'reference'    => 'OWN-COURIER-' . date('YmdHis'),
            'status'       => 'PROVIDER_SELECTED',
            'fee'          => 0.00, // No delivery fee charged by Ngenzi
            'message'      => 'Mzigo wako utaandaliwa dukani. Msafirishaji wako ataweza kuja kuuchukua kwa kutaja namba ya agizo.',
            'tracking_url' => null
        ];
    }

    public function getDeliveryStatus(string $trackingRef): array {
        return [
            'success'    => true,
            'status'     => 'PROVIDER_SELECTED',
            'raw_status' => 'AWAITING_CUSTOMER_COURIER',
            'notes'      => 'Inasubiri kuwasili kwa msafirishaji wa mteja dukani.',
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    public function cancelDelivery(string $trackingRef, string $reason = ''): array {
        return [
            'success' => true,
            'message' => 'Mipango ya msafirishaji wa mteja imesitishwa.'
        ];
    }

    public function getTrackingInformation(string $trackingRef): array {
        return [
            'available'         => false,
            'tracking_url'      => null,
            'estimated_arrival' => 'Inategemea msafirishaji wako binafsi',
            'driver'            => null
        ];
    }

    public function estimateDeliveryFee(array $origin, array $destination, array $packageInfo = []): ?float {
        return 0.00; // Customer pays courier directly
    }
}
