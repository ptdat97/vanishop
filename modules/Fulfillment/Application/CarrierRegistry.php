<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Fulfillment\Contracts\SourcingStrategy;
use Modules\Tenancy\Contracts\Data\SettingsScope;
use Modules\Tenancy\Contracts\Settings;

/**
 * Carrier và SourcingStrategy có hiệu lực trong phạm vi hiện tại (plugin tắt thì không có mặt).
 */
final class CarrierRegistry
{
    public const CARRIERS_TAG = ShippingCarrier::CARRIERS_TAG;

    /** @deprecated dùng {@see SourcingStrategy::TAG} (public API). */
    public const SOURCING_TAG = SourcingStrategy::TAG;

    public function __construct(
        private readonly Extensions $extensions,
        private readonly Settings $settings,
    ) {}

    public function carrier(string $code): ?ShippingCarrier
    {
        return $this->carriers()[$code] ?? null;
    }

    /**
     * @return array<string, ShippingCarrier>
     */
    public function carriers(): array
    {
        $carriers = [];
        foreach ($this->extensions->tagged(self::CARRIERS_TAG) as $carrier) {
            if ($carrier instanceof ShippingCarrier) {
                $carriers[$carrier->code()] = $carrier;
            }
        }

        return $carriers;
    }

    /**
     * Sourcing theo cấu hình `core.fulfillment.sourcing` của brand (mặc định VANI_FULFILLMENT_SOURCING).
     */
    public function sourcing(int $brandId): ?SourcingStrategy
    {
        $default = (string) config('vanishop.fulfillment.sourcing', 'reserved_locations');
        $code = (string) $this->settings->get('core', 'fulfillment.sourcing', SettingsScope::brand($brandId), $default);
        $strategy = $this->extensions->select(SourcingStrategy::TAG, $code, $default);

        return $strategy instanceof SourcingStrategy ? $strategy : null;
    }
}
