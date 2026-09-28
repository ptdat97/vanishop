<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

final readonly class StoredResponse
{
    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        public int $status,
        public array $body,
    ) {}
}
