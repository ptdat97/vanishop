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
        return 'Sản phẩm đã xem';
    }

    public function fields(): array
    {
        return [
            FieldDefinition::string('title', 'Tiêu đề', max: 120),
            FieldDefinition::int('limit', 'Số sản phẩm (1–20)', help: 'Mặc định 8.'),
            FieldDefinition::select('layout', 'Kiểu hiển thị', ['grid' => 'Lưới', 'scroll' => 'Trượt ngang']),
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
