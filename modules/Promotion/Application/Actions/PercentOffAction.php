<?php

declare(strict_types=1);

namespace Modules\Promotion\Application\Actions;

use Modules\Promotion\Contracts\PromotionAction;
use Modules\Promotion\Domain\DiscountMath;

/**
 * Giảm theo % trên từng dòng đủ điều kiện. Config: {basis_points: 1..10000} (1000 = 10%).
 */
final class PercentOffAction implements PromotionAction
{
    public function type(): string
    {
        return 'percent_off';
    }

    public function label(): string
    {
        return 'Giảm theo %';
    }

    public function validateConfig(array $config): array
    {
        $bp = $config['basis_points'] ?? null;

        return is_int($bp) && $bp >= 1 && $bp <= 10_000 ? [] : ['Phần trăm giảm phải từ 0,01% đến 100%.'];
    }

    public function apply(array $remaining, array $config, string $currencyCode): array
    {
        return DiscountMath::percentOff($remaining, (int) ($config['basis_points'] ?? 0));
    }
}
