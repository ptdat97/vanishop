<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Promotion\Contracts\CartContentRule;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\Ineligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\ExplainsIneligibility;

/**
 * Rule: tổng số lượng sản phẩm trong các dòng ứng viên phải đạt tối thiểu min_quantity.
 * Cấu hình: {"min_quantity": 2}
 */
final class MinQuantityRule implements CartContentRule, ExplainsIneligibility
{
    public const TYPE = 'min_quantity';

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('vani-promotion-rules::texts.min_quantity_rule.minimum_product_quantity');
    }

    public function validateConfig(array $config): array
    {
        $min = $config['min_quantity'] ?? null;
        if (! is_numeric($min) || (int) $min <= 0) {
            return [__('vani-promotion-rules::texts.min_quantity_rule.min_quantity_must_positive_integer')];
        }

        return [];
    }

    public function explain(PromotionContext $context, array $config, Eligibility $candidates): ?Ineligibility
    {
        $missing = (int) ($config['min_quantity'] ?? 0) - $this->candidateQuantity($context, $candidates);

        return $missing > 0 ? new Ineligibility(__('vani-promotion-rules::texts.min_quantity_rule.buy_more', ['count' => $missing])) : null;
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        $threshold = (int) ($config['min_quantity'] ?? 0);
        if ($threshold <= 0) {
            return $candidates;
        }

        return $this->candidateQuantity($context, $candidates) >= $threshold ? $candidates : Eligibility::none();
    }

    private function candidateQuantity(PromotionContext $context, Eligibility $candidates): int
    {
        $quantity = 0;
        foreach ($candidates->keys as $key) {
            $quantity += $context->line($key)->quantity ?? 0;
        }

        return $quantity;
    }
}
