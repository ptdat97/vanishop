<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\TaxCalculator;

/**
 * VAT đã gồm trong giá bán (bán lẻ VN): thuế dòng = round_half_up(total × rate / (10000 + rate)).
 * Thuế suất theo cấu hình (`vanishop.tax.vat_rate_bp`) — kế toán/pháp chế xác nhận mức hiện hành.
 */
final class VnVatInclusiveTax implements TaxCalculator
{
    public function __construct(private readonly int $rateBasisPoints) {}

    public function code(): string
    {
        return 'vn_vat_inclusive';
    }

    public function calculate(TotalsContext $context): array
    {
        $taxes = [];
        foreach ($context->lines as $line) {
            $taxes[$line->key] = ['rate_bp' => $this->rateBasisPoints, 'amount' => $line->total()->includedTax($this->rateBasisPoints)->amount];
        }

        return $taxes;
    }
}
