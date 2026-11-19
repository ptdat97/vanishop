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
            ['products', __('catalog::texts.catalog_navigation.products')],
            ['brands', __('catalog::texts.catalog_navigation.brand')],
            ['categories', __('catalog::texts.catalog_navigation.category')],
            ['collections', __('catalog::texts.catalog_navigation.collections')],
            ['option-types', __('catalog::texts.catalog_navigation.variant_options')],
            ['attributes', __('catalog::texts.catalog_navigation.attributes')],
            ['link-types', __('catalog::texts.catalog_navigation.link_type')],
            ['filters', __('catalog::texts.catalog_navigation.filters')],
        ]);
    }
}
