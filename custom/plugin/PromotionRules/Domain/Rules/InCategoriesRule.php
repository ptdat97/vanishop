<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Catalog\Contracts\CategoryDirectory;
use Modules\Promotion\Contracts\CartContentRule;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;

/**
 * Rule (0.5.0): chỉ các dòng có sản phẩm thuộc danh mục chỉ định, kể cả danh mục con ("Đầm" gồm "Đầm dự tiệc").
 * Cấu hình: {"category_ids": [3, 8]}
 */
final class InCategoriesRule implements CartContentRule
{
    public const TYPE = 'in_categories';

    public function __construct(private readonly CategoryDirectory $categories) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('vani-promotion-rules::texts.in_categories_rule.category');
    }

    public function validateConfig(array $config): array
    {
        return InProductsRule::validIds($config['category_ids'] ?? null) ? [] : [__('vani-promotion-rules::texts.in_categories_rule.category_ids_required')];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        $wanted = array_fill_keys(array_map('intval', (array) ($config['category_ids'] ?? [])), true);
        $productIds = [];
        foreach ($candidates->keys as $key) {
            $productId = $context->line($key)?->productId;
            if ($productId !== null) {
                $productIds[] = $productId;
            }
        }
        $byProduct = $this->categories->idsForProducts($productIds);

        return new Eligibility(array_values(array_filter($candidates->keys, function (int $key) use ($context, $byProduct, $wanted): bool {
            foreach ($byProduct[$context->line($key)->productId ?? 0] ?? [] as $categoryId) {
                if (isset($wanted[$categoryId])) {
                    return true;
                }
            }

            return false;
        })));
    }
}
