<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

/**
 * Tab điều hướng của Catalog Admin. URL sinh ở server vì đường dẫn Admin cấu hình được.
 */
final class CatalogNavigation
{
    /**
     * @return list<array{key: string, label: string, url: string}>
     */
    public static function all(): array
    {
        return array_map(fn (array $item): array => [
            'key' => $item[0],
            'label' => $item[1],
            'url' => route("admin.catalog.{$item[0]}.index"),
        ], [
            ['products', 'Sản phẩm'],
            ['brands', 'Thương hiệu'],
            ['categories', 'Danh mục'],
            ['collections', 'Bộ sưu tập'],
            ['attributes', 'Thuộc tính'],
            ['colors', 'Màu'],
            ['sizes', 'Size'],
        ]);
    }
}
