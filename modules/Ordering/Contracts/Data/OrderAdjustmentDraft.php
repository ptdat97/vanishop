<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

final readonly class OrderAdjustmentDraft
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function __construct(
        public string $type,
        public string $source,
        public ?string $code,
        public string $label,
        public int $amount,
        public array $meta = [],
    ) {}
}
