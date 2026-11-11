<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Truy vấn tìm kiếm sản phẩm hiển thị trên storefront.
 * Giữa các nhóm là AND; trong cùng một nhóm (nhiều màu, nhiều giá trị của một thuộc tính) là OR.
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
     * @param  list<string>  $colorFamilies
     * @param  array<int, list<int>>  $attributeValueIds  attribute id => value ids
     * @param  list<string>  $sizeCodes  mã size (`sizes.code`): sản phẩm có biến thể đang bán thuộc một trong các size (0.4.3)
     * @param  int|null  $priceMin  giá niêm yết thấp nhất của sản phẩm ≥ mức này (đơn vị nhỏ nhất, 0.4.4); sản phẩm chưa có giá bị loại khi lọc giá
     * @param  int|null  $priceMax  giá niêm yết thấp nhất của sản phẩm ≤ mức này
     */
    public function __construct(
        public array $brandIds,
        public int $now,
        public string $text = '',
        public ?int $categoryId = null,
        public ?int $collectionId = null,
        public array $colorFamilies = [],
        public array $attributeValueIds = [],
        public string $sort = self::SORT_NEWEST,
        public int $page = 1,
        public int $perPage = 24,
        public array $sizeCodes = [],
        public ?int $priceMin = null,
        public ?int $priceMax = null,
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
