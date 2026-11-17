<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

#[PluginApi]
final readonly class CarrierShipment
{
    public function __construct(
        public string $trackingNumber,
        public ?string $labelUrl = null,
        public ?string $serviceCode = null,
    ) {}
}
