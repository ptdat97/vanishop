<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts\Data;

final readonly class InboxMessage
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $id,
        public string $system,
        public string $externalEventId,
        public string $messageType,
        public array $payload,
        public ?string $correlationId,
        public int $attempt,
    ) {}
}
