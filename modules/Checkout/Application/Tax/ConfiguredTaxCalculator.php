<?php

declare(strict_types=1);

namespace Modules\Checkout\Application\Tax;

use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\TaxCalculator;
use Modules\Extension\Contracts\Extensions;
use Modules\Tenancy\Contracts\Settings;
use RuntimeException;

/**
 * TaxCalculator theo cấu hình `core.tax.calculator` của cửa hàng (mặc định VANI_TAX_CALCULATOR). Không có hiệu lực
 * (plugin thuế tắt) → mặc định → `none` của Core.
 * Không đăng ký vào tag — chỉ là điểm chọn.
 */
final class ConfiguredTaxCalculator implements TaxCalculator
{
    public function __construct(
        private readonly Extensions $extensions,
        private readonly Settings $settings,
        private readonly string $defaultCode,
    ) {}

    public function code(): string
    {
        return 'configured';
    }

    public function calculate(TotalsContext $context): array
    {
        $code = (string) $this->settings->get('core', 'tax.calculator', $this->defaultCode);
        $calculator = $this->extensions->select(TaxCalculator::TAG, $code, $this->defaultCode)
            ?? $this->extensions->select(TaxCalculator::TAG, NoTax::CODE);

        return $calculator instanceof TaxCalculator
            ? $calculator->calculate($context)
            : throw new RuntimeException("TaxCalculator [{$code}] chưa được đăng ký.");
    }
}
