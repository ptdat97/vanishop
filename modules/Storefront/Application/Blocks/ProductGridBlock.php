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
        return 'Lưới sản phẩm';
    }

    public function fields(): array
    {
        return [
            FieldDefinition::string('title', 'Tiêu đề', max: 120),
            FieldDefinition::select('source', 'Nguồn', ['newest' => 'Mới nhất', 'category' => 'Danh mục', 'collection' => 'Bộ sưu tập', 'brand' => 'Thương hiệu'], required: true),
            FieldDefinition::string('slug', 'Slug danh mục/bộ sưu tập/thương hiệu', help: 'Bỏ trống khi nguồn là "Mới nhất".', max: 128),
            FieldDefinition::int('limit', 'Số sản phẩm (1–24)'),
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

        return ['title' => (string) ($config['title'] ?? ''), 'products' => $this->products->listing($filters, $locale, $now)['items']];
    }

    public function view(): string
    {
        return 'theme::blocks.product_grid';
    }
}
