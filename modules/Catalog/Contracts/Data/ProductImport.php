<?php

declare(strict_types=1);

namespace Modules\Catalog\Contracts\Data;

/**
 * Dữ liệu nhập một sản phẩm (CatalogImporter, 0.3.27). Thương hiệu/danh mục/màu/size nhận theo mã-slug: chưa có thì
 * tạo. Ảnh là đường dẫn file local (đã chuẩn bị kích thước) — Core lưu qua thư viện media (khử trùng theo checksum,
 * sinh bản WebP).
 */
final readonly class ProductImport
{
    /**
     * @param  array<string, array{name: string, description?: string|null}>  $translations  locale => nội dung
     * @param  array{slug: string, code: string, name: string}|null  $brand
     * @param  list<array{slug: string, name: string, parent_slug?: string|null}>  $categories  danh mục đầu tiên là danh mục chính
     * @param  list<array{code: string, name: string, hex: string, family?: string, images: list<string>}>  $colors  family: ColorFamily (mặc định multi)
     * @param  list<string>  $sizes  mã size (S, M, L…)
     */
    public function __construct(
        public string $styleCode,
        public string $slug,
        public array $translations,
        public ?array $brand,
        public array $categories,
        public array $colors,
        public array $sizes,
        public bool $active = true,
    ) {}
}
