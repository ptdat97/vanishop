<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Bộ lọc sản phẩm theo ngôn ngữ của storefront (slug, mã) — Catalog tự đổi sang id.
 */
final readonly class ProductFilters
{
    /**
     * @param  list<string>  $brandSlugs  thương hiệu (rỗng = mọi brand)
     * @param  array<string, list<string>>  $attributes  mã thuộc tính => mã giá trị
     * @param  int|null  $priceMin  khoảng giá niêm yết (đơn vị nhỏ nhất của tiền tệ cửa hàng)
     * @param  array<string, list<string>>  $options  mã loại tuỳ chọn => mã lọc (nhóm màu với loại ô màu, mã giá trị với loại khác)
     */
    public function __construct(
        public int $now,
        public string $text = '',
        public ?string $categorySlug = null,
        public ?string $collectionSlug = null,
        public array $attributes = [],
        public string $sort = ProductSearchQuery::SORT_NEWEST,
        public int $page = 1,
        public int $perPage = 24,
        public array $brandSlugs = [],
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public array $options = [],
    ) {}
}
