<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Promotion\Contracts\CartContentRule;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;

/**
 * Rule (0.5.0): bỏ các dòng đang bán dưới giá gốc (giá sale/thành viên của bảng giá, giá gạch) — "không áp cho hàng đang
 * giảm giá". Không nhận cấu hình.
 */
final class ExcludeOnSaleRule implements CartContentRule
{
    public const TYPE = 'exclude_on_sale';

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('vani-promotion-rules::texts.exclude_on_sale_rule.exclude_on_sale');
    }

    public function validateConfig(array $config): array
    {
        return $config === [] ? [] : [__('vani-promotion-rules::texts.first_order_only_rule.rule_takes_no_settings')];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        return new Eligibility(array_values(array_filter($candidates->keys, fn (int $key): bool => $context->line($key)?->isDiscounted() === false)));
    }
}
