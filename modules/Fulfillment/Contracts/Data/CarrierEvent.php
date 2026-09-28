<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts\Data;

use DateTimeImmutable;

/**
 * Sự kiện vận đơn đã xác minh + chuẩn hoá. `status` là một giá trị của ShipmentStatus (created, picked_up, …).
 */
final readonly class CarrierEvent
{
    /**
     * @param  array<string, mixed>  $maskedPayload
     * @param  array<string, mixed>  $acknowledgement
     */
    public function __construct(
        public string $trackingNumber,
        public string $status,
        public string $eventId,
        public DateTimeImmutable $occurredAt,
        public ?string $description = null,
        public array $maskedPayload = [],
        public array $acknowledgement = ['ok' => true],
    ) {}
}
