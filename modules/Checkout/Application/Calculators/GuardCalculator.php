<?php

declare(strict_types=1);

namespace Modules\Checkout\Application\Calculators;

use Illuminate\Support\Facades\Log;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Checkout\Contracts\TotalsCalculator;

/**
 * Chốt chặn cuối: giảm giá không âm và không vượt tổng dòng (plugin tính sai không làm đơn âm tiền).
 */
final class GuardCalculator implements TotalsCalculator
{
    public function code(): string
    {
        return 'guard';
    }

    public function priority(): int
    {
        return 900;
    }

    public function calculate(TotalsContext $context): TotalsContext
    {
        return $context->withLines(array_map(function (TotalsLine $line) use ($context): TotalsLine {
            $clamped = max(0, min($line->discount->amount, $line->subtotal->amount));
            if ($clamped !== $line->discount->amount) {
                Log::warning('Totals guard: giảm giá dòng vượt giới hạn, đã cắt.', ['variant_id' => $line->variantId, 'discount' => $line->discount->amount, 'subtotal' => $line->subtotal->amount]);

                return $line->withDiscount($context->money($clamped));
            }

            return $line;
        }, $context->lines));
    }
}
