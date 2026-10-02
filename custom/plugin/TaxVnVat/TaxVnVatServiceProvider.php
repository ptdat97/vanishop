<?php

declare(strict_types=1);

namespace Plugin\TaxVnVat;

use Modules\Checkout\Contracts\TaxCalculator;
use Modules\Extension\PluginServiceProvider;
use Plugin\TaxVnVat\Infrastructure\VnVatInclusiveTax;

/**
 * Plugin hệ thống (ADR-029): VAT Việt Nam gồm trong giá. Chọn bằng `core.tax.calculator = vn_vat_inclusive`
 * (mặc định); tắt plugin → Core dùng `none` (không thuế).
 */
final class TaxVnVatServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.tax-vn-vat';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function register(): void
    {
        $this->mergeConfigFrom($this->pluginPath('Config/tax-vn-vat.php'), 'vani.tax-vn-vat');
    }

    public function boot(): void
    {
        $this->settings([
            ['key' => 'rate_bp', 'label' => 'Thuế suất (basis points)', 'type' => 'int', 'help' => '1000 = 10%. Để trống = theo VANI_VAT_RATE_BP.'],
        ]);

        $this->contribute(TaxCalculator::TAG, VnVatInclusiveTax::class);
    }
}
