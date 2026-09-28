<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Checkout\Contracts\Data\TotalsContext;

/**
 * Extension point (tag `vani.totals.calculators`). Priority: subtotal 100, promotion 200, plugin 300–399,
 * shipping 500, tax 800, guard 900. Chỉ tính, không ghi DB, không I/O mạng.
 */
interface TotalsCalculator
{
    public function code(): string;

    public function priority(): int;

    public function calculate(TotalsContext $context): TotalsContext;
}
