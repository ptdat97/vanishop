<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain;

use Modules\Shared\Domain\Money\Money;

/**
 * Một mức giá của variant trong một bảng giá đang hiệu lực.
 */
final readonly class PriceCandidate
{
    public function __construct(
        public int $priceListId,
        public string $priceListCode,
        public PriceListType $type,
        public int $priority,
        public Money $amount,
        public ?Money $compareAt = null,
    ) {}
}
