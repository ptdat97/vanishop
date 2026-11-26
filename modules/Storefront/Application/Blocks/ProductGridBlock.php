<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Blocks;

use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Application\ProductViews;
use Modules\Storefront\Contracts\StorefrontBlock;

final class ProductGridBlock implements StorefrontBlock
{
    public function __construct(private readonly ProductViews $products) {}

    public function type(): string
    {
        return 'product_grid';
    }

    public function label(): string
    {
        return __('storefront::texts.product_grid_block.product_grid');
    }

    public function fields(): array
    {
        return [
            FieldDefinition::string('title', __('storefront::texts.product_grid_block.title'), max: 120),
            FieldDefinition::select('source', __('storefront::texts.product_grid_block.source'), ['newest' => __('storefront::texts.product_grid_block.newest'), 'category' => __('storefront::texts.product_grid_block.category'), 'collection' => __('storefront::texts.product_grid_block.collections'), 'brand' => __('storefront::texts.product_grid_block.brand')], required: true),
            FieldDefinition::string('slug', __('storefront::texts.product_grid_block.category_collection_brand_slug'), help: __('storefront::texts.product_grid_block.leave_empty_when_source_newest'), max: 128),
            FieldDefinition::int('limit', __('storefront::texts.product_grid_block.number_products_1_24')),
        ];
    }

    public function resolve(array $config, string $locale): array
    {
        $now = now()->getTimestamp();
        $slug = trim((string) ($config['slug'] ?? ''));
        $source = (string) ($config['source'] ?? 'newest');
        $filters = new ProductFilters(
            now: $now,
            categorySlug: $source === 'category' && $slug !== '' ? $slug : null,
            collectionSlug: $source === 'collection' && $slug !== '' ? $slug : null,
            perPage: max(1, min(24, (int) ($config['limit'] ?? 8))),
            brandSlugs: $source === 'brand' && $slug !== '' ? [$slug] : [],
        );

        return ['title' => (string) ($config['title'] ?? ''), 'products' => $this->products->forFilters($filters, $locale)];
    }

    public function view(): string
    {
        return 'theme::blocks.product_grid';
    }
}
