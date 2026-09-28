<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

final readonly class PromotionResult
{
    /**
     * @param  list<AppliedPromotion>  $applied
     * @param  list<VoucherRejection>  $rejectedVouchers
     */
    public function __construct(
        public array $applied,
        public array $rejectedVouchers,
    ) {}

    /**
     * @return array<int, int> khoá dòng => tổng giảm (minor unit)
     */
    public function discountByLine(): array
    {
        $totals = [];
        foreach ($this->applied as $promotion) {
            foreach ($promotion->lineDiscounts as $key => $discount) {
                $totals[$key] = ($totals[$key] ?? 0) + $discount->amount;
            }
        }

        return $totals;
    }

    public function total(string $currencyCode): Money
    {
        return Money::of(array_sum($this->discountByLine()), $currencyCode);
    }
}
