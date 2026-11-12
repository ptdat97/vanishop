<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Truy vấn tìm kiếm sản phẩm hiển thị trên storefront.
 * Giữa các nhóm là AND; trong cùng một nhóm (nhiều giá trị của một loại tuỳ chọn, của một thuộc tính) là OR.
 */
final readonly class ProductSearchQuery
{
    public const SORT_NEWEST = 'newest';

    public const SORT_CODE = 'code';

    /** Theo giá niêm yết thấp nhất của sản phẩm (0.4.4); sản phẩm chưa có giá xếp cuối. */
    public const SORT_PRICE_ASC = 'price_asc';

    public const SORT_PRICE_DESC = 'price_desc';

    public const SORTS = [self::SORT_NEWEST, self::SORT_CODE, self::SORT_PRICE_ASC, self::SORT_PRICE_DESC];

    public const MAX_PER_PAGE = 60;

    /**
     * @param  list<int>  $brandIds  lọc theo thương hiệu (rỗng = mọi brand)
     * @param  array<int, list<int>>  $attributeValueIds  attribute id => value ids
     * @param  int|null  $priceMin  giá niêm yết thấp nhất của sản phẩm ≥ mức này (đơn vị nhỏ nhất, 0.4.4); sản phẩm chưa có giá bị loại khi lọc giá
     * @param  int|null  $priceMax  giá niêm yết thấp nhất của sản phẩm ≤ mức này
     * @param  array<string, list<string>>  $options  mã loại tuỳ chọn => mã lọc (ADR-045, 0.4.26): sản phẩm có SKU đang bán mang giá trị khớp. Mã lọc của loại kiểu ô màu là nhóm màu (`white`, `black`…), của loại khác là mã giá trị (`S`, `COTTON`…)
     */
    public function __construct(
        public array $brandIds,
        public int $now,
        public string $text = '',
        public ?int $categoryId = null,
        public ?int $collectionId = null,
        public array $attributeValueIds = [],
        public string $sort = self::SORT_NEWEST,
        public int $page = 1,
        public int $perPage = 24,
        public ?int $priceMin = null,
        public ?int $priceMax = null,
        public array $options = [],
    ) {}

    public function offset(): int
    {
        return (max(1, $this->page) - 1) * $this->limit();
    }

    public function limit(): int
    {
        return min(max(1, $this->perPage), self::MAX_PER_PAGE);
    }
}
