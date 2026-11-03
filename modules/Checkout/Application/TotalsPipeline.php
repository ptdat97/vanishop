<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Modules\Checkout\Contracts\Data\Totals;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Checkout\Contracts\TotalsCalculator;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Domain\Money\Money;

/**
 * Chạy các TotalsCalculator theo priority (docs/03-domains/cart-checkout.md §2). Cùng một pipeline cho
 * xem tổng (quote) và PlaceOrder.
 */
final class TotalsPipeline
{
    /** Từ priority này trở lên là chốt chặn: hook `vani.service.checkout.totals` chạy ngay trước. */
    public const GUARD_PRIORITY = 900;

    public function __construct(private readonly Extensions $extensions) {}

    public function run(TotalsContext $context): Totals
    {
        $calculators = array_values(array_filter($this->extensions->tagged(TotalsCalculator::TAG), fn (object $calculator): bool => $calculator instanceof TotalsCalculator));
        usort($calculators, fn (TotalsCalculator $a, TotalsCalculator $b): int => [$a->priority(), $a->code()] <=> [$b->priority(), $b->code()]);

        $filtered = false;
        foreach ($calculators as $calculator) {
            // Plugin sửa kết quả sau mọi calculator, trước chốt chặn (guard ≥ 900) — giảm giá sai vẫn bị cắt.
            if (! $filtered && $calculator->priority() >= self::GUARD_PRIORITY) {
                $context = hook_filter('vani.service.checkout.totals', $context);
                $filtered = true;
            }
            $context = $calculator->calculate($context);
        }
        if (! $filtered) {
            $context = hook_filter('vani.service.checkout.totals', $context);
        }

        $sum = fn (callable $pick): Money => array_reduce($context->lines, fn (Money $carry, TotalsLine $line): Money => $carry->add($pick($line)), $context->money(0));
        $shipping = $context->shipping?->fee ?? $context->money(0);

        return new Totals(
            currencyCode: $context->currencyCode,
            lines: $context->lines,
            adjustments: $context->adjustments,
            subtotal: $sum(fn (TotalsLine $line): Money => $line->subtotal),
            discount: $sum(fn (TotalsLine $line): Money => $line->discount),
            shipping: $context->shipping,
            tax: $sum(fn (TotalsLine $line): Money => $line->tax ?? $context->money(0)),
            grandTotal: $context->linesTotal()->add($shipping),
            rejectedVouchers: $context->promotions->rejectedVouchers ?? [],
            promotions: $context->promotions,
        );
    }
}
