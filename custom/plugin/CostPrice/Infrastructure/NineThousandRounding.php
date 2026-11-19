<?php

declare(strict_types=1);

namespace Plugin\CostPrice\Infrastructure;

use Modules\Pricing\Contracts\PriceRounding;

/**
 * "Giá đẹp" đuôi 9.000 (EP v3 X4 — tham chiếu `PriceRounding`): làm tròn LÊN tới số gần nhất kết thúc bằng 9.000 —
 * 191.000 → 199.000, 200.000 → 209.000; dưới 9.000 giữ nguyên.
 */
final class NineThousandRounding implements PriceRounding
{
    public function code(): string
    {
        return 'tail_9000';
    }

    public function label(): string
    {
        return __('vani-cost-price::texts.nine_thousand_rounding.charm_price_ending_9_000');
    }

    public function round(int $amount): int
    {
        if ($amount < 9000) {
            return $amount;
        }

        return (int) (ceil(($amount - 9000) / 10_000) * 10_000 + 9000);
    }
}
