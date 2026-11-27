<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Promotion\Contracts\CartContentRule;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\Ineligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\ExplainsIneligibility;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\MoneyFormatter;

/**
 * Rule: giá trị giỏ hàng (tổng tiền các dòng ứng viên) phải đạt tối thiểu min_subtotal.
 * Cấu hình: {"min_subtotal": 500000}
 */
final class MinOrderSubtotalRule implements CartContentRule, ExplainsIneligibility
{
    public const TYPE = 'min_order_subtotal';

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('vani-promotion-rules::texts.min_order_subtotal_rule.minimum_order_value');
    }

    public function validateConfig(array $config): array
    {
        $min = $config['min_subtotal'] ?? null;
        if (! is_numeric($min) || (int) $min <= 0) {
            return [__('vani-promotion-rules::texts.min_order_subtotal_rule.min_subtotal_must_positive_number')];
        }

        return [];
    }

    public function explain(PromotionContext $context, array $config, Eligibility $candidates): ?Ineligibility
    {
        $shortfall = (int) ($config['min_subtotal'] ?? 0) - $this->candidateSubtotal($context, $candidates);
        if ($shortfall <= 0) {
            return null;
        }
        $money = Money::of($shortfall, $context->currencyCode);

        return new Ineligibility(__('vani-promotion-rules::texts.min_order_subtotal_rule.buy_more', ['amount' => app(MoneyFormatter::class)->format($money)]), $money);
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        $threshold = (int) ($config['min_subtotal'] ?? 0);
        if ($threshold <= 0) {
            return $candidates;
        }

        return $this->candidateSubtotal($context, $candidates) >= $threshold ? $candidates : Eligibility::none();
    }

    private function candidateSubtotal(PromotionContext $context, Eligibility $candidates): int
    {
        $subtotal = 0;
        foreach ($candidates->keys as $key) {
            $subtotal += $context->line($key)?->subtotal->amount ?? 0;
        }

        return $subtotal;
    }
}
