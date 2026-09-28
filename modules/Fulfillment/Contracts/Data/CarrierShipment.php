<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts\Data;

final readonly class CarrierShipment
{
    public function __construct(
        public string $trackingNumber,
        public ?string $labelUrl = null,
        public ?string $serviceCode = null,
    ) {}
}
