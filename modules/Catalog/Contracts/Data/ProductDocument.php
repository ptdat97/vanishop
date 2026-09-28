<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Tài liệu tìm kiếm của một style (dùng cho SearchProvider cần index ngoài như Meilisearch).
 */
final readonly class ProductDocument
{
    /**
     * @param  array<string, string>  $names  locale => tên
     * @param  list<int>  $categoryIds  mọi danh mục chứa style kèm tổ tiên của chúng
     * @param  list<int>  $collectionIds
     * @param  list<string>  $colorFamilies
     * @param  list<int>  $attributeValueIds  giá trị của thuộc tính spec + filterable
     */
    public function __construct(
        public int $id,
        public int $brandId,
        public string $styleCode,
        public string $slug,
        public string $status,
        public ?int $publishedFrom,
        public ?int $publishedTo,
        public array $names,
        public string $searchText,
        public array $categoryIds,
        public array $collectionIds,
        public array $colorFamilies,
        public array $attributeValueIds,
        public int $createdAt,
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
            'color_families' => $this->colorFamilies,
            'attribute_value_ids' => $this->attributeValueIds,
            'created_at' => $this->createdAt,
        ];
    }
}
