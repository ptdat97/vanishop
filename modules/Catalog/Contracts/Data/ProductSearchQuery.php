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

    public const MAX_PER_PAGE = 60;

    /**
     * @param  list<int>  $brandIds  lọc theo thương hiệu (rỗng = mọi brand)
     * @param  list<string>  $colorFamilies
     * @param  array<int, list<int>>  $attributeValueIds  attribute id => value ids
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
