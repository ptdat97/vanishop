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
    /** @deprecated dùng {@see TotalsCalculator::TAG} (public API). */
    public const TAG = TotalsCalculator::TAG;

    public function __construct(private readonly Extensions $extensions) {}

    public function run(TotalsContext $context): Totals
    {
        $calculators = array_values(array_filter($this->extensions->tagged(self::TAG), fn (object $calculator): bool => $calculator instanceof TotalsCalculator));
        usort($calculators, fn (TotalsCalculator $a, TotalsCalculator $b): int => [$a->priority(), $a->code()] <=> [$b->priority(), $b->code()]);

        foreach ($calculators as $calculator) {
            $context = $calculator->calculate($context);
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
            channelId: $context->channelId,
        );
    }
}
