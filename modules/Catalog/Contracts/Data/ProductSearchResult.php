<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

final readonly class ProductSearchResult
{
    /**
     * @param  list<int>  $styleIds  theo thứ tự hiển thị
     * @param  array{attribute_values: array<int, int>, brands?: array<int, int>, options?: array<string, array<string, int>>, price?: array{min: int, max: int}|null}  $facets  brands: brand id => số sản phẩm; options: mã loại tuỳ chọn => mã lọc => số sản phẩm (0.4.26); price: khoảng giá niêm yết của kết quả khi bỏ bộ lọc giá (0.4.4)
     */
    public function __construct(
        public array $styleIds,
        public int $total,
        public array $facets = ['attribute_values' => [], 'brands' => [], 'options' => []],
    ) {}
}
