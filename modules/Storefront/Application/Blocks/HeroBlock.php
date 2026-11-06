<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Blocks;

use Modules\Catalog\Contracts\MediaUrls;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;

final class HeroBlock implements StorefrontBlock
{
    public function __construct(private readonly MediaUrls $media) {}

    public function type(): string
    {
        return 'hero';
    }

    public function label(): string
    {
        return 'Banner chính';
    }

    public function fields(): array
    {
        return [
            FieldDefinition::string('title', 'Tiêu đề', required: true, max: 120),
            FieldDefinition::text('subtitle', 'Mô tả ngắn', max: 300),
            FieldDefinition::media('image', 'Ảnh', help: 'Chọn từ Thư viện ảnh — tự có bản vừa màn hình (srcset).'),
            FieldDefinition::string('link_label', 'Chữ trên nút', max: 40),
            FieldDefinition::string('link_url', 'Đường dẫn nút', help: 'Đường dẫn trong website, vd. /thuong-hieu/urbanx', max: 300),
        ];
    }

    public function resolve(array $config, string $locale): array
    {
        $link = (string) ($config['link_url'] ?? '');

        return [
            'title' => (string) ($config['title'] ?? ''),
            'subtitle' => (string) ($config['subtitle'] ?? ''),
            // `image_url` (URL nhập tay, trước 0.3.90) chỉ còn đọc cho cấu hình cũ chưa chuyển sang Thư viện ảnh.
            'imageUrl' => ($image = is_numeric($config['image'] ?? null) ? (int) $config['image'] : null) !== null ? $this->media->image($image, 1600) ?? '' : self::legacyUrl($config['image_url'] ?? ''),
            'imageSrcset' => $image !== null ? $this->media->srcset($image) : '',
            'linkLabel' => (string) ($config['link_label'] ?? ''),
            // Chỉ nhận đường dẫn nội bộ hoặc http(s) — chặn javascript:.
            'linkUrl' => preg_match('#^(/|https?://)#', $link) === 1 ? $link : '',
        ];
    }

    private static function legacyUrl(mixed $url): string
    {
        $url = trim((string) $url);

        return preg_match('#^(/(?!/)|https?://)#i', $url) === 1 ? $url : '';
    }

    public function view(): string
    {
        return 'theme::blocks.hero';
    }
}
