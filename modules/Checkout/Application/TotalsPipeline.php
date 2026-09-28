<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Illuminate\Contracts\Container\Container;
use Modules\Checkout\Contracts\Data\Totals;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Checkout\Contracts\TotalsCalculator;
use Modules\Shared\Domain\Money\Money;

/**
 * Chạy các TotalsCalculator theo priority (docs/03-domains/cart-checkout.md §2). Cùng một pipeline cho
 * xem tổng (quote) và PlaceOrder.
 */
final class TotalsPipeline
{
    public const TAG = 'vani.totals.calculators';

    public function __construct(private readonly Container $container) {}

    public function run(TotalsContext $context): Totals
    {
        $calculators = iterator_to_array($this->container->tagged(self::TAG), false);
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
        );
    }
}
