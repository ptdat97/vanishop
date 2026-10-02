<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts\Data;

/**
 * Event canonical gửi cho đối tác. `type` dạng `<aggregate>.<việc>` (order.created, payment.captured…).
 */
final readonly class IntegrationEvent
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $type,
        public string $aggregateType,
        public string $aggregateId,
        public array $data,
        public string $schemaVersion = '1',
    ) {}
}
