<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

final readonly class AppliedPromotion
{
    /**
     * @param  array<int, Money>  $lineDiscounts  khoá dòng => số tiền giảm (>= 0)
     */
    public function __construct(
        public int $promotionId,
        public ?int $voucherId,
        public ?string $voucherCode,
        public string $name,
        public array $lineDiscounts,
        public Money $total,
    ) {}
}
