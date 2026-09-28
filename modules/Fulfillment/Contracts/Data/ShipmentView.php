<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts\Data;

final readonly class ShipmentView
{
    /**
     * @param  list<array{sku: string, quantity: int}>  $lines
     * @param  list<array{status: string, description: ?string, at: string}>  $events
     */
    public function __construct(
        public int $id,
        public string $publicId,
        public string $carrierCode,
        public string $carrierLabel,
        public ?string $serviceCode,
        public ?string $trackingNumber,
        public string $status,
        public int $codAmount,
        public ?string $lastError,
        public array $lines,
        public array $events,
        /** ISO 8601, null nếu chưa giao. */
        public ?string $deliveredAt = null,
        public int $locationId = 0,
    ) {}
}
