<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts\Data;

final readonly class OutboxMessage
{
    /**
     * @param  array<string, mixed>  $payload  envelope chuẩn: event_id, event_type, schema_version, occurred_at, source, correlation_id, data
     */
    public function __construct(
        public string $messageId,
        public string $target,
        public string $messageType,
        public string $schemaVersion,
        public ?int $brandId,
        public string $aggregateType,
        public string $aggregateId,
        public array $payload,
        public ?string $correlationId,
        public int $attempt,
    ) {}
}
