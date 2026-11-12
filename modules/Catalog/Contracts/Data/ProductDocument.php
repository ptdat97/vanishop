<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Tài liệu tìm kiếm của một style (dùng cho SearchProvider có chỉ mục ngoài; provider `database` đọc thẳng bảng).
 */
final readonly class ProductDocument
{
    /**
     * @param  array<string, string>  $names  locale => tên
     * @param  list<int>  $categoryIds  mọi danh mục chứa style kèm tổ tiên của chúng
     * @param  list<int>  $collectionIds
     * @param  list<int>  $attributeValueIds  giá trị của thuộc tính spec + filterable
     * @param  int|null  $listPriceMin  giá niêm yết thấp nhất (đơn vị nhỏ nhất, 0.4.4); null = chưa có giá
     * @param  list<string>  $options  `<mã loại>:<mã lọc>` của các SKU đang bán (ADR-045, 0.4.26), vd. `color:white`, `size:M`
     */
    public function __construct(
        public int $id,
        public ?int $brandId,
        public string $styleCode,
        public string $slug,
        public string $status,
        public ?int $publishedFrom,
        public ?int $publishedTo,
        public array $names,
        public string $searchText,
        public array $categoryIds,
        public array $collectionIds,
        public array $attributeValueIds,
        public int $createdAt,
        public ?int $listPriceMin = null,
        public array $options = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'brand_id' => $this->brandId,
            'style_code' => $this->styleCode,
            'slug' => $this->slug,
            'status' => $this->status,
            'published_from' => $this->publishedFrom,
            'published_to' => $this->publishedTo,
            'names' => $this->names,
            'search_text' => $this->searchText,
            'category_ids' => $this->categoryIds,
            'collection_ids' => $this->collectionIds,
            'attribute_value_ids' => $this->attributeValueIds,
            'created_at' => $this->createdAt,
            'list_price_min' => $this->listPriceMin,
            'options' => $this->options,
        ];
    }
}
