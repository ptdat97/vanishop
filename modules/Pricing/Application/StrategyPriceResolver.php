<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Pricing\Contracts\PricingStrategy;

/**
 * PriceResolver dùng PricingStrategy theo cấu hình (VANI_PRICING_STRATEGY) trong các strategy đã đăng ký.
 */
final class StrategyPriceResolver implements PriceResolver
{
    public const TAG = 'vani.pricing.strategies';

    public function __construct(
        private readonly Container $container,
        private readonly string $strategyCode,
    ) {}

    public function forVariants(array $variantIds, PricingContext $context): array
    {
        return $this->strategy()->resolve(array_values(array_unique($variantIds)), $context);
    }

    private function strategy(): PricingStrategy
    {
        /** @var iterable<PricingStrategy> $strategies */
        $strategies = $this->container->tagged(self::TAG);

        foreach ($strategies as $strategy) {
            if ($strategy->code() === $this->strategyCode) {
                return $strategy;
            }
        }

        throw new InvalidArgumentException("Pricing strategy [{$this->strategyCode}] chưa được đăng ký.");
    }
}
