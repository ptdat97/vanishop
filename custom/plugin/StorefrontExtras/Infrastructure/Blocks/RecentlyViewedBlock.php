<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;

/**
 * Đã xem (đặc tả §3.2): HTML chỉ là khung rỗng (trang cache được); JS lấy danh sách trong trình duyệt
 * (`localStorage` `vani.recently_viewed` — theme ghi ở trang sản phẩm) rồi gọi `GET /products?ids=`. Rỗng → ẩn.
 */
final class RecentlyViewedBlock implements StorefrontBlock
{
    public function type(): string
    {
        return 'extras_recently_viewed';
    }

    public function label(): string
    {
        return __('vani-storefront-extras::texts.recently_viewed_block.recently_viewed_products');
    }

    public function fields(): array
    {
        return [
            FieldDefinition::string('title', __('vani-storefront-extras::texts.recently_viewed_block.title'), max: 120),
            FieldDefinition::int('limit', __('vani-storefront-extras::texts.recently_viewed_block.number_products_1_20'), help: __('vani-storefront-extras::texts.recently_viewed_block.default_8')),
            FieldDefinition::select('layout', __('vani-storefront-extras::texts.recently_viewed_block.display_style'), ['grid' => __('vani-storefront-extras::texts.recently_viewed_block.grid'), 'scroll' => __('vani-storefront-extras::texts.recently_viewed_block.horizontal_slider')]),
        ];
    }

    public function resolve(array $config, string $locale): array
    {
        return [
            'title' => (string) ($config['title'] ?? ''),
            'limit' => max(1, min(20, (int) ($config['limit'] ?? 8) ?: 8)),
            'layout' => ($config['layout'] ?? 'grid') === 'scroll' ? 'scroll' : 'grid',
            'exclude' => [],
        ];
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.recently-viewed';
    }
}
