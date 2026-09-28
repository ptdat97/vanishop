<?php

declare(strict_types=1);

namespace Modules\Checkout\Application\Calculators;

use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Checkout\Contracts\TaxCalculator;
use Modules\Checkout\Contracts\TotalsCalculator;

final class TaxStage implements TotalsCalculator
{
    public function __construct(private readonly TaxCalculator $tax) {}

    public function code(): string
    {
        return 'tax';
    }

    public function priority(): int
    {
        return 800;
    }

    public function calculate(TotalsContext $context): TotalsContext
    {
        $taxes = $this->tax->calculate($context);

        return $context->withLines(array_map(
            fn (TotalsLine $line): TotalsLine => $line->withTax($taxes[$line->key]['rate_bp'] ?? 0, $context->money($taxes[$line->key]['amount'] ?? 0)),
            $context->lines,
        ));
    }
}
