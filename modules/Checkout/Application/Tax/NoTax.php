<?php

declare(strict_types=1);

namespace Modules\Checkout\Application\Tax;

use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\TaxCalculator;

/**
 * Không tính thuế — implementation trung lập của Core, dự phòng khi chưa bật plugin thuế (ADR-029).
 */
final class NoTax implements TaxCalculator
{
    public const CODE = 'none';

    public function code(): string
    {
        return self::CODE;
    }

    public function calculate(TotalsContext $context): array
    {
        return [];
    }
}
