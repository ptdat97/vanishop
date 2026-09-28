<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Bộ lọc sản phẩm theo ngôn ngữ của storefront (slug, mã) — Catalog tự đổi sang id trong phạm vi brand.
 */
final readonly class ProductFilters
{
    /**
     * @param  list<int>  $brandIds
     * @param  list<string>  $colorFamilies
     * @param  array<string, list<string>>  $attributes  mã thuộc tính => mã giá trị
     */
    public function __construct(
        public array $brandIds,
        public int $now,
        public string $text = '',
        public ?string $categorySlug = null,
        public ?string $collectionSlug = null,
        public array $colorFamilies = [],
        public array $attributes = [],
        public string $sort = ProductSearchQuery::SORT_NEWEST,
        public int $page = 1,
        public int $perPage = 24,
    ) {}
}
