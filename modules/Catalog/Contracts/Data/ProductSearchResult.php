<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

final readonly class ProductSearchResult
{
    /**
     * @param  list<int>  $styleIds  theo thứ tự hiển thị
     * @param  array{color_families: array<string, int>, attribute_values: array<int, int>, brands?: array<int, int>, sizes?: array<string, int>, price?: array{min: int, max: int}|null}  $facets  brands: brand id => số sản phẩm; sizes: mã size => số sản phẩm (0.4.3); price: khoảng giá niêm yết của kết quả khi bỏ bộ lọc giá (0.4.4)
     */
    public function __construct(
        public array $styleIds,
        public int $total,
        public array $facets = ['color_families' => [], 'attribute_values' => [], 'brands' => []],
    ) {}
}
