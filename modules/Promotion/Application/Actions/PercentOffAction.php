<?php

declare(strict_types=1);

namespace Modules\Promotion\Application\Actions;

use Modules\Promotion\Contracts\PromotionAction;
use Modules\Promotion\Domain\DiscountMath;
use Modules\Shared\Domain\Money\Money;

/**
 * Giảm theo % trên từng dòng đủ điều kiện. Config: {basis_points: 1..10000 (1000 = 10%), max_amount?: tổng giảm tối đa (minor unit, 0.5.109)}.
 */
final class PercentOffAction implements PromotionAction
{
    public function type(): string
    {
        return 'percent_off';
    }

    public function label(): string
    {
        return __('promotion::texts.percent_off_action.decrease');
    }

    public function validateConfig(array $config): array
    {
        $bp = $config['basis_points'] ?? null;
        $errors = is_int($bp) && $bp >= 1 && $bp <= 10_000 ? [] : [__('promotion::texts.percent_off_action.discount_percentage_must_between_0')];
        $max = $config['max_amount'] ?? null;
        if ($max !== null && (! is_int($max) || $max < 1)) {
            $errors[] = __('promotion::texts.percent_off_action.max_amount_positive');
        }

        return $errors;
    }

    public function apply(array $remaining, array $config, string $currencyCode): array
    {
        $discounts = DiscountMath::percentOff($remaining, (int) ($config['basis_points'] ?? 0));
        $max = $config['max_amount'] ?? null;

        // Giảm tối đa (0.5.109): kẹp tổng của khuyến mãi, chia lại theo tỷ trọng dòng.
        return is_int($max) && $max > 0 ? DiscountMath::capTotal($discounts, Money::of($max, $currencyCode)) : $discounts;
    }
}
