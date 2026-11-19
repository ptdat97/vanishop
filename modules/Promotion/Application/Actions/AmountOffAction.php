<?php

declare(strict_types=1);

namespace Modules\Promotion\Application\Actions;

use Modules\Promotion\Contracts\PromotionAction;
use Modules\Promotion\Domain\DiscountMath;
use Modules\Shared\Domain\Money\Money;

/**
 * Giảm một số tiền cho cả nhóm dòng đủ điều kiện, phân bổ theo tỷ trọng. Config: {amount: > 0 (minor unit)}.
 */
final class AmountOffAction implements PromotionAction
{
    public function type(): string
    {
        return 'amount_off';
    }

    public function label(): string
    {
        return __('promotion::texts.amount_off_action.decrease_amount');
    }

    public function validateConfig(array $config): array
    {
        $amount = $config['amount'] ?? null;

        return is_int($amount) && $amount > 0 ? [] : [__('promotion::texts.amount_off_action.discount_amount_must_greater_than')];
    }

    public function apply(array $remaining, array $config, string $currencyCode): array
    {
        return DiscountMath::amountOff($remaining, Money::of((int) ($config['amount'] ?? 0), $currencyCode));
    }
}
