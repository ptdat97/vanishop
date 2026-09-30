<?php

declare(strict_types=1);

namespace Modules\Checkout\Application\Tax;

use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Checkout\Contracts\TaxCalculator;
use Modules\Extension\Contracts\Extensions;
use Modules\Tenancy\Contracts\Data\SettingsScope;
use Modules\Tenancy\Contracts\Settings;
use RuntimeException;

/**
 * TaxCalculator theo cấu hình `core.tax.calculator` của kênh/brand của giỏ (mặc định VANI_TAX_CALCULATOR).
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
        $brandIds = array_values(array_unique(array_map(fn (TotalsLine $line): int => $line->brandId, $context->lines)));
        $scope = new SettingsScope($context->channelId, count($brandIds) === 1 ? $brandIds[0] : null);
        $code = (string) $this->settings->get('core', 'tax.calculator', $scope, $this->defaultCode);
        $calculator = $this->extensions->select(TaxCalculator::TAG, $code, $this->defaultCode);

        return $calculator instanceof TaxCalculator
            ? $calculator->calculate($context)
            : throw new RuntimeException("TaxCalculator [{$code}] chưa được đăng ký.");
    }
}
