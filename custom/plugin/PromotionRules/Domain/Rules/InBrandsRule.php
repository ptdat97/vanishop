<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Catalog\Contracts\CatalogReader;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\PromotionRule;

/**
 * Rule: chỉ áp dụng cho sản phẩm thuộc một trong các thương hiệu chỉ định (brand là thuộc tính catalog, ADR-028).
 * Cấu hình: {"slugs": ["urbanx", "lumiere"]}
 */
final class InBrandsRule implements PromotionRule
{
    public const TYPE = 'in_brands';

    public function __construct(private readonly CatalogReader $catalog) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return 'Thuộc thương hiệu';
    }

    public function validateConfig(array $config): array
    {
        $slugs = $config['slugs'] ?? null;
        if (! is_array($slugs) || $slugs === []) {
            return ['Cần khai báo danh sách slug "slugs" không rỗng.'];
        }

        foreach ($slugs as $slug) {
            if (! is_string($slug) || trim($slug) === '') {
                return ['Mỗi slug trong "slugs" phải là chuỗi không rỗng.'];
            }
        }

        return [];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        $slugs = array_fill_keys(array_map('strval', (array) ($config['slugs'] ?? [])), true);
        $brandIds = [];
        foreach ($this->catalog->brands() as $brand) {
            if (isset($slugs[$brand['slug']])) {
                $brandIds[$brand['id']] = true;
            }
        }
        if ($brandIds === []) {
            return Eligibility::none();
        }

        $matching = [];
        foreach ($candidates->keys as $key) {
            $brandId = $context->line($key)?->brandId;
            if ($brandId !== null && isset($brandIds[$brandId])) {
                $matching[] = $key;
            }
        }

        return new Eligibility($matching);
    }
}
