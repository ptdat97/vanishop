<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

final readonly class ResolvedPrice
{
    public function __construct(
        public int $variantId,
        public Money $amount,
        public ?Money $compareAt,
        public ?int $discountPercent,
        public string $priceListCode,
    ) {}
}
