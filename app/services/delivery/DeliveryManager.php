<?php
/**
 * NGENZI MOTO SPARE PARTS
 * Delivery Manager & Provider Orchestrator (Phase 17)
 * Handles Provider Resolution, Configurable Shipping Toggles,
 * Fee Computations & Safe Fallbacks.
 * Dar es Salaam, Tanzania
 */

namespace App\Services\Delivery;

use App\Models\Setting;
use Exception;

class DeliveryManager {

    /**
     * Map provider code to provider instance
     */
    public static function resolveProvider(string $providerCode): DeliveryProviderInterface {
        $code = strtoupper(trim($providerCode));

        switch ($code) {
            case 'BOLT':
            case 'BOLT_DELIVERY':
                return new BoltDeliveryProvider();

            case 'CUSTOMER_COURIER':
            case 'OWN_COURIER':
                return new CustomerCourierProvider();

            case 'CUSTOMER_PICKUP':
            case 'PICKUP':
                return new PickupDeliveryProvider();

            case 'OTHER_COURIER':
            case 'COURIER':
                return new OtherCourierProvider();

            case 'STORE_DELIVERY':
            case 'STANDARD':
            case 'MANUAL':
            default:
                return new StoreDeliveryProvider();
        }
    }

    /**
     * Get list of enabled delivery providers based on dynamic settings
     */
    public static function getEnabledProviders(): array {
        $providers = [];

        if ((string)Setting::get('delivery_store_enabled', '1') === '1') {
            $providers['STORE_DELIVERY'] = new StoreDeliveryProvider();
        }

        if ((string)Setting::get('delivery_bolt_enabled', '1') === '1') {
            $providers['BOLT'] = new BoltDeliveryProvider();
        }

        if ((string)Setting::get('delivery_pickup_enabled', '1') === '1') {
            $providers['CUSTOMER_PICKUP'] = new PickupDeliveryProvider();
        }

        if ((string)Setting::get('delivery_own_courier_enabled', '1') === '1') {
            $providers['CUSTOMER_COURIER'] = new CustomerCourierProvider();
        }

        if ((string)Setting::get('delivery_other_courier_enabled', '1') === '1') {
            $providers['OTHER_COURIER'] = new OtherCourierProvider();
        }

        if (empty($providers)) {
            $providers['CUSTOMER_PICKUP'] = new PickupDeliveryProvider();
        }

        return $providers;
    }

    /**
     * Calculate delivery fee based on provider and subtotal
     */
    public static function calculateFee(string $providerCode, float $subtotal): float {
        $code = strtoupper(trim($providerCode));

        if ($code === 'CUSTOMER_PICKUP' || $code === 'PICKUP' || $code === 'CUSTOMER_COURIER' || $code === 'OWN_COURIER') {
            return 0.00;
        }

        $freeThreshold = (float)Setting::get('free_delivery_threshold', '100000.00');
        $flatRate = (float)Setting::get('delivery_flat_rate', '5000.00');

        if ($code === 'STORE_DELIVERY' || $code === 'STANDARD' || $code === 'MANUAL') {
            if ($subtotal >= $freeThreshold && $subtotal > 0) {
                return 0.00; // Free delivery threshold achieved
            }
            return $flatRate;
        }

        if ($code === 'BOLT' || $code === 'BOLT_DELIVERY') {
            // Bolt estimated fee or standard flat rate
            return $flatRate;
        }

        if ($code === 'OTHER_COURIER') {
            return $flatRate;
        }

        return $flatRate;
    }

    /**
     * Get array of provider data formatted for customer checkout UI
     */
    public static function getCheckoutOptions(float $subtotal): array {
        $enabled = self::getEnabledProviders();
        $freeThreshold = (float)Setting::get('free_delivery_threshold', '100000.00');
        $flatRate = (float)Setting::get('delivery_flat_rate', '5000.00');
        $options = [];

        foreach ($enabled as $code => $provider) {
            $fee = self::calculateFee($code, $subtotal);
            $isFree = ($fee === 0.00);

            $options[] = [
                'code'           => $code,
                'name'           => $provider->getName(),
                'description'    => $provider->getDescription(),
                'fee'            => $fee,
                'fee_formatted'  => $isFree ? 'BURE (Free)' : 'TSh ' . number_format($fee, 2),
                'status'         => $provider->getProviderStatus(),
                'is_free'        => $isFree,
                'is_configured'  => ($provider->getProviderStatus() === 'READY')
            ];
        }

        return $options;
    }
}
