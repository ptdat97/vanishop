<?php

declare(strict_types=1);

namespace Modules\Promotion\Tests\Fixtures;

use Modules\Promotion\Contracts\CartContentRule;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\Ineligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\ExplainsIneligibility;
use Modules\Shared\Domain\Money\Money;

/** Rule ngưỡng tạm tính (CartContentRule) — được kiểm tra lại khi khách bớt hàng. */
final class MinSubtotalFixtureRule implements CartContentRule, ExplainsIneligibility
{
    public function type(): string
    {
        return 'fixture_min_subtotal';
    }

    public function label(): string
    {
        return 'Đơn từ X';
    }

    public function validateConfig(array $config): array
    {
        return [];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        return $context->subtotal()->amount >= (int) $config['min'] ? $candidates : new Eligibility([]);
    }

    public function explain(PromotionContext $context, array $config, Eligibility $candidates): ?Ineligibility
    {
        $shortfall = Money::of((int) $config['min'], $context->currencyCode)->subtract($context->subtotal());

        return $shortfall->isPositive() ? new Ineligibility("Mua thêm {$shortfall->amount}đ để dùng mã", $shortfall) : null;
    }
}
