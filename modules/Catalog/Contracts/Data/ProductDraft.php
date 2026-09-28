<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Dữ liệu sản phẩm sắp lưu — tham số của hook vani.product.before_save (plugin kiểm tra quy tắc riêng của brand).
 */
final readonly class ProductDraft
{
    /**
     * @param  array<string, array<string, string|null>>  $translations
     * @param  list<int>  $categoryIds
     * @param  array<int, mixed>  $attributes  attribute id => giá trị
     */
    public function __construct(
        public int $brandId,
        public ?int $styleId,
        public string $styleCode,
        public string $slug,
        public string $status,
        public array $translations,
        public array $categoryIds,
        public array $attributes,
    ) {}
}
