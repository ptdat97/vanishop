<?php

declare(strict_types=1);

namespace Plugin\TaxVnVat\Infrastructure;

use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\TaxCalculator;
use Modules\Tenancy\Contracts\Settings;
use Plugin\TaxVnVat\TaxVnVatServiceProvider;

/**
 * VAT đã gồm trong giá bán (bán lẻ VN): thuế dòng = round_half_up(total × rate / (10000 + rate)).
 * Mã `vn_vat_inclusive` giữ nguyên từ khi còn nằm trong Core (cấu hình `core.tax.calculator` cũ vẫn đúng).
 */
final class VnVatInclusiveTax implements TaxCalculator
{
    public function __construct(private readonly Settings $settings) {}

    public function code(): string
    {
        return 'vn_vat_inclusive';
    }

    public function calculate(TotalsContext $context): array
    {
        $rate = (int) ($this->settings->get(TaxVnVatServiceProvider::ID, 'rate_bp') ?? config('vani.tax-vn-vat.rate_bp', 1000));

        $taxes = [];
        foreach ($context->lines as $line) {
            $taxes[$line->key] = ['rate_bp' => $rate, 'amount' => $line->total()->includedTax($rate)->amount];
        }

        return $taxes;
    }
}
