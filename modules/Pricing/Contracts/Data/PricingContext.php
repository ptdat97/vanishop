<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts\Data;

final readonly class PricingContext
{
    public function __construct(
        public int $now,
        public ?int $customerGroupId = null,
    ) {}
}
