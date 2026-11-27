<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Promotion\Contracts\CartContentRule;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;

/**
 * Rule (0.5.0): chỉ các dòng thuộc sản phẩm chỉ định (mọi size/màu của sản phẩm). Cấu hình: {"product_ids": [12, 15]}
 */
final class InProductsRule implements CartContentRule
{
    public const TYPE = 'in_products';

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('vani-promotion-rules::texts.in_products_rule.product');
    }

    public function validateConfig(array $config): array
    {
        return self::validIds($config['product_ids'] ?? null) ? [] : [__('vani-promotion-rules::texts.in_products_rule.product_ids_required')];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        $productIds = array_fill_keys(array_map('intval', (array) ($config['product_ids'] ?? [])), true);

        return new Eligibility(array_values(array_filter($candidates->keys, fn (int $key): bool => isset($productIds[$context->line($key)->productId ?? 0]))));
    }

    /** Danh sách id dương, không rỗng. */
    public static function validIds(mixed $ids): bool
    {
        if (! is_array($ids) || $ids === []) {
            return false;
        }
        foreach ($ids as $id) {
            if (! is_int($id) || $id <= 0) {
                return false;
            }
        }

        return true;
    }
}
