<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use InvalidArgumentException;
use Modules\Extension\Contracts\Extensions;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Contracts\PricingStrategy;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Data\SettingsScope;
use Modules\Tenancy\Contracts\Settings;

/**
 * PriceResolver dùng PricingStrategy theo cấu hình `core.pricing.strategy` của kênh/brand (mặc định
 * VANI_PRICING_STRATEGY) trong các strategy có hiệu lực ở phạm vi hiện tại (strategy của plugin chỉ có mặt khi
 * plugin được bật).
 */
final class StrategyPriceResolver implements PriceResolver
{
    /** @deprecated dùng {@see PricingStrategy::TAG} (public API). */
    public const TAG = PricingStrategy::TAG;

    public function __construct(
        private readonly Extensions $extensions,
        private readonly Settings $settings,
        private readonly CurrentContext $context,
        private readonly string $defaultCode,
    ) {}

    public function forVariants(array $variantIds, PricingContext $context): array
    {
        return $this->strategy($context->channelId)->resolve(array_values(array_unique($variantIds)), $context);
    }

    private function strategy(int $channelId): PricingStrategy
    {
        $scope = SettingsScope::fromContext($this->context->has() ? $this->context->scope() : null, $channelId);
        $code = (string) $this->settings->get('core', 'pricing.strategy', $scope, $this->defaultCode);
        $strategy = $this->extensions->select(PricingStrategy::TAG, $code, $this->defaultCode);

        return $strategy instanceof PricingStrategy ? $strategy : throw new InvalidArgumentException("Pricing strategy [{$code}] chưa được đăng ký.");
    }
}
