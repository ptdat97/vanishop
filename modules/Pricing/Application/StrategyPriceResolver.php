<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use InvalidArgumentException;
use Modules\Extension\Contracts\Extensions;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Contracts\PricingStrategy;
use Modules\Tenancy\Contracts\Settings;

/**
 * PriceResolver dùng PricingStrategy theo cấu hình `core.pricing.strategy` của cửa hàng (mặc định
 * VANI_PRICING_STRATEGY); strategy của plugin chỉ có mặt khi plugin được bật.
 */
final class StrategyPriceResolver implements PriceResolver
{
    public function __construct(
        private readonly Extensions $extensions,
        private readonly Settings $settings,
        private readonly string $defaultCode,
    ) {}

    public function forVariants(array $variantIds, PricingContext $context): array
    {
        return $this->strategy()->resolve(array_values(array_unique($variantIds)), $context);
    }

    private function strategy(): PricingStrategy
    {
        $code = (string) $this->settings->get('core', 'pricing.strategy', $this->defaultCode);
        $strategy = $this->extensions->select(PricingStrategy::TAG, $code, $this->defaultCode);

        return $strategy instanceof PricingStrategy ? $strategy : throw new InvalidArgumentException("Pricing strategy [{$code}] chưa được đăng ký.");
    }
}
