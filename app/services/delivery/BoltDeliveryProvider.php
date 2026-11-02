<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Bolt Delivery Provider (Phase 17)
 * Optional Express Shipping via Bolt
 * Dar es Salaam, Tanzania
 */

namespace App\Services\Delivery;

use App\Models\Setting;
use App\Core\Helper;

class BoltDeliveryProvider implements DeliveryProviderInterface {

    protected bool $isConfigured = false;
    protected string $apiKey = '';
    protected string $apiUrl = '';

    public function __construct() {
        $configured = (string)Setting::get('bolt_api_configured', '0');
        $this->apiKey = (string)Setting::get('bolt_api_key', '');
        $this->apiUrl = (string)Setting::get('bolt_api_url', '');

        // Strict validation: Only marked as configured if explicit flag and valid key exist
        $this->isConfigured = ($configured === '1' && !empty($this->apiKey) && !empty($this->apiUrl));
    }

    public function getCode(): string {
        return 'BOLT';
    }

    public function getName(): string {
        return 'Bolt Express Delivery';
    }

    public function getDescription(): string {
        return 'Usafirishaji wa haraka kupitia dereva wa Bolt (Pikipiki/Gari). Agizo litapokelewa na kuwasilishwa haraka.';
    }

    public function getProviderStatus(): string {
        if ($this->isConfigured) {
            return 'READY';
        }
        return 'NOT CONFIGURED';
    }

    /**
     * Create / Request Delivery with Bolt
     */
    public function createDelivery(array $orderData, array $deliveryData): array {
        $orderNumber = $orderData['order_number'] ?? ('ORD-' . time());
        $recipientName = $deliveryData['recipient_name'] ?? 'Mteja';
        $recipientPhone = $deliveryData['recipient_phone'] ?? '';
        $deliveryFee = (float)($deliveryData['delivery_fee'] ?? Setting::get('delivery_flat_rate', '5000.00'));

        if (!$this->isConfigured) {
            // SAFE FALLBACK / MANUAL WORKFLOW:
            // Do NOT fake API response. Mark status as MANUAL_FALLBACK / PROVIDER_SELECTED.
            // Generate standard pending internal reference for staff to arrange via Bolt app/portal.
            $manualRef = 'BOLT-MANUAL-' . strtoupper(substr(md5($orderNumber . time()), 0, 8));

            return [
                'success'       => true,
                'is_manual'     => true,
                'reference'     => $manualRef,
                'status'        => 'PROVIDER_SELECTED',
                'fee'           => $deliveryFee,
                'message'       => 'Chaguo la Bolt limechaguliwa. Kwa kuwa muunganisho wa moja kwa moja wa API ya Bolt haujasanidiwa, agizo litapangwa na wafanyakazi wa Ngenzi kupitia Bolt.',
                'tracking_url'  => null,
                'instructions'  => 'Mhudumu wa Ngenzi atapanga dereva wa Bolt na kukutumia namba ya ufuatiliaji au kumpa dereva maelezo ya eneo lako.'
            ];
        }

        // When real API credentials are configured in future:
        // Here live API request will be executed using verified endpoints.
        return [
            'success'      => false,
            'is_manual'    => false,
            'reference'    => '',
            'status'       => 'FAILED',
            'fee'          => $deliveryFee,
            'message'      => 'API ya Bolt haijasanidiwa rasmi bado kwenye seva.',
            'tracking_url' => null
        ];
    }

    public function getDeliveryStatus(string $trackingRef): array {
        if (!$this->isConfigured) {
            return [
                'success'    => true,
                'status'     => 'PROVIDER_SELECTED',
                'raw_status' => 'MANUAL_DISPATCH_PENDING',
                'notes'      => 'Inasubiri uthibitisho wa dereva wa Bolt kutoka kwa msimamizi wa duka.',
                'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        return [
            'success'    => false,
            'status'     => 'NOT CONFIGURED',
            'raw_status' => 'UNCONFIGURED',
            'notes'      => 'Muunganisho wa API ya Bolt haujasanidiwa.',
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }

    public function cancelDelivery(string $trackingRef, string $reason = ''): array {
        return [
            'success' => true,
            'message' => 'Ombi la uwasilishaji wa Bolt limesitishwa kwenye mfumo.'
        ];
    }

    public function getTrackingInformation(string $trackingRef): array {
        return [
            'available'         => false,
            'tracking_url'      => null,
            'estimated_arrival' => 'Ndani ya dakika 30-60 mara baada ya dereva kuchukua mzigo',
            'driver'            => null
        ];
    }

    public function estimateDeliveryFee(array $origin, array $destination, array $packageInfo = []): ?float {
        // Fallback to configured standard city delivery fee
        return (float)Setting::get('delivery_flat_rate', '5000.00');
    }

    /**
     * Official external Bolt destination link (safe, official root URL only)
     */
    public static function getOfficialExternalUrl(): string {
        return 'https://bolt.eu/en-tz/';
    }
}
