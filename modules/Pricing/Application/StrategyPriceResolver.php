<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use InvalidArgumentException;
use Modules\Extension\Contracts\Extensions;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Contracts\PricingStrategy;

/**
 * PriceResolver dùng PricingStrategy theo cấu hình (VANI_PRICING_STRATEGY) trong các strategy có hiệu lực
 * trong phạm vi hiện tại (strategy do plugin cung cấp chỉ có mặt khi plugin được bật).
 */
final class StrategyPriceResolver implements PriceResolver
{
    public const TAG = 'vani.pricing.strategies';

    public function __construct(
        private readonly Extensions $extensions,
        private readonly string $strategyCode,
    ) {}

    public function forVariants(array $variantIds, PricingContext $context): array
    {
        return $this->strategy()->resolve(array_values(array_unique($variantIds)), $context);
    }

    private function strategy(): PricingStrategy
    {
        foreach ($this->extensions->tagged(self::TAG) as $strategy) {
            if ($strategy instanceof PricingStrategy && $strategy->code() === $this->strategyCode) {
                return $strategy;
            }
        }

        throw new InvalidArgumentException("Pricing strategy [{$this->strategyCode}] chưa được đăng ký.");
    }
}
