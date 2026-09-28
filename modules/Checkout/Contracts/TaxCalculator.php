<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Checkout\Contracts\Data\TotalsContext;

/**
 * Extension point (tag `vani.tax.calculators`, chọn theo `vanishop.tax.calculator`). Tách thuế theo từng dòng
 * SAU khi đã trừ giảm giá phân bổ. Mặc định `vn_vat_inclusive`.
 */
interface TaxCalculator
{
    public function code(): string;

    /**
     * @return array<int, array{rate_bp: int, amount: int}> khoá dòng => thuế suất và tiền thuế (minor unit)
     */
    public function calculate(TotalsContext $context): array;
}
