<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Fulfillment\Contracts\SourcingStrategy;

/**
 * Carrier và SourcingStrategy có hiệu lực trong phạm vi hiện tại (plugin tắt thì không có mặt).
 */
final class CarrierRegistry
{
    public const CARRIERS_TAG = ShippingCarrier::CARRIERS_TAG;

    /** @deprecated dùng {@see SourcingStrategy::TAG} (public API). */
    public const SOURCING_TAG = SourcingStrategy::TAG;

    public function __construct(private readonly Extensions $extensions) {}

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

    public function sourcing(string $code): ?SourcingStrategy
    {
        foreach ($this->extensions->tagged(self::SOURCING_TAG) as $strategy) {
            if ($strategy instanceof SourcingStrategy && $strategy->code() === $code) {
                return $strategy;
            }
        }

        return null;
    }
}
