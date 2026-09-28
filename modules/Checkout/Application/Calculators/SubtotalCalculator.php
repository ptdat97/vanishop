<?php

declare(strict_types=1);

namespace Modules\Checkout\Application\Calculators;

use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\TotalsCalculator;

/**
 * Bắt đầu pipeline từ trạng thái sạch: mọi dòng chưa có giảm giá/thuế (pipeline chạy lại được nhiều lần).
 */
final class SubtotalCalculator implements TotalsCalculator
{
    public function code(): string
    {
        return 'subtotal';
    }

    public function priority(): int
    {
        return 100;
    }

    public function calculate(TotalsContext $context): TotalsContext
    {
        return $context->withLines(array_map(fn ($line) => $line->withDiscount($context->money(0))->withTax(0, $context->money(0)), $context->lines));
    }
}
