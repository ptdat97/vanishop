<?php

declare(strict_types=1);

namespace Modules\Tenancy\Tests\Feature\Fixtures;

use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\TaxCalculator;

/**
 * TaxCalculator giả (vd. brand bán hàng miễn thuế) — dùng để kiểm tra chọn strategy theo brand.
 */
final class ZeroTax implements TaxCalculator
{
    public function code(): string
    {
        return 'zero_tax';
    }

    public function calculate(TotalsContext $context): array
    {
        $result = [];
        foreach ($context->lines as $line) {
            $result[$line->key] = ['rate_bp' => 0, 'amount' => 0];
        }

        return $result;
    }
}
