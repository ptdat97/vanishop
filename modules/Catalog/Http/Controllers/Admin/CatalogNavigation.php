<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Modules\Brand\Persistence\Models\Brand;

/**
 * Tab điều hướng trong brand workspace Catalog. URL sinh ở server vì đường dẫn Admin cấu hình được.
 */
final class CatalogNavigation
{
    /**
     * @return list<array{key: string, label: string, url: string}>
     */
    public static function for(Brand $brand): array
    {
        return array_map(fn (array $item): array => [
            'key' => $item[0],
            'label' => $item[1],
            'url' => route("admin.catalog.{$item[0]}.index", ['brand' => $brand->slug]),
        ], [
            ['products', 'Sản phẩm'],
            ['categories', 'Danh mục'],
            ['collections', 'Bộ sưu tập'],
            ['attributes', 'Thuộc tính'],
            ['colors', 'Màu'],
            ['sizes', 'Size'],
        ]);
    }
}
