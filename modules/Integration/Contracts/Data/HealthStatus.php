<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts\Data;

final readonly class HealthStatus
{
    public function __construct(public bool $healthy, public ?string $message = null) {}

    public static function healthy(): self
    {
        return new self(true);
    }

    public static function unhealthy(string $message): self
    {
        return new self(false, $message);
    }
}
