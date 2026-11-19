<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Promotion\Contracts\CartContentRule;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;

/**
 * Rule: tổng số lượng sản phẩm trong các dòng ứng viên phải đạt tối thiểu min_quantity.
 * Cấu hình: {"min_quantity": 2}
 */
final class MinQuantityRule implements CartContentRule
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

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        $threshold = (int) ($config['min_quantity'] ?? 0);
        if ($threshold <= 0) {
            return $candidates;
        }

        $totalQty = 0;
        foreach ($candidates->keys as $key) {
            $line = $context->line($key);
            if ($line !== null) {
                $totalQty += $line->quantity;
            }
        }

        return $totalQty >= $threshold ? $candidates : Eligibility::none();
    }
}
