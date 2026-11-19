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
        $this->translations($this->pluginPath('Resources/lang'), 'vani-tax-vn-vat');
        $this->settings([
            ['key' => 'rate_bp', 'label' => 'vani-tax-vn-vat::texts.tax_vn_vat_service_provider.tax_rate_basis_points', 'type' => 'int', 'help' => 'vani-tax-vn-vat::texts.tax_vn_vat_service_provider.text_1000_10_leave_empty_use'],
        ]);

        $this->contribute(TaxCalculator::TAG, VnVatInclusiveTax::class);
    }
}
