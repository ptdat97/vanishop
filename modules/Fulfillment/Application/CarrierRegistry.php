<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Illuminate\Contracts\Container\Container;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Fulfillment\Contracts\SourcingStrategy;

final class CarrierRegistry
{
    public const CARRIERS_TAG = 'vani.shipping.carriers';

    public const SOURCING_TAG = 'vani.fulfillment.sourcing';

    public function __construct(private readonly Container $container) {}

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
        foreach ($this->container->tagged(self::CARRIERS_TAG) as $carrier) {
            $carriers[$carrier->code()] = $carrier;
        }

        return $carriers;
    }

    public function sourcing(string $code): ?SourcingStrategy
    {
        foreach ($this->container->tagged(self::SOURCING_TAG) as $strategy) {
            if ($strategy->code() === $code) {
                return $strategy;
            }
        }

        return null;
    }
}
