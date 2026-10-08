<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Promotion\Contracts\CartContentRule;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;

/**
 * Rule: giá trị giỏ hàng (tổng tiền các dòng ứng viên) phải đạt tối thiểu min_subtotal.
 * Cấu hình: {"min_subtotal": 500000}
 */
final class MinOrderSubtotalRule implements CartContentRule
{
    public const TYPE = 'min_order_subtotal';

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return 'Giá trị đơn tối thiểu';
    }

    public function validateConfig(array $config): array
    {
        $min = $config['min_subtotal'] ?? null;
        if (! is_numeric($min) || (int) $min <= 0) {
            return ['Cần khai báo "min_subtotal" là số dương.'];
        }

        return [];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        $threshold = (int) ($config['min_subtotal'] ?? 0);
        if ($threshold <= 0) {
            return $candidates;
        }

        $candidateSubtotal = 0;
        foreach ($candidates->keys as $key) {
            $line = $context->line($key);
            if ($line !== null) {
                $candidateSubtotal += $line->subtotal->amount;
            }
        }

        return $candidateSubtotal >= $threshold ? $candidates : Eligibility::none();
    }
}
