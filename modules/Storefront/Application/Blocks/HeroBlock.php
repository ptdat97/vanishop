<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Blocks;

use Modules\Catalog\Contracts\MediaUrls;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;

final class HeroBlock implements StorefrontBlock
{
    public const TYPE = 'hero';

    public function __construct(private readonly MediaUrls $media) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('storefront::texts.hero_block.hero_banner');
    }

    public function fields(): array
    {
        return [
            FieldDefinition::string('title', __('storefront::texts.hero_block.title'), required: true, max: 120),
            FieldDefinition::text('subtitle', __('storefront::texts.hero_block.short_description'), max: 300),
            FieldDefinition::media('image', __('storefront::texts.hero_block.image'), help: __('storefront::texts.hero_block.choose_from_media_library_responsive')),
            FieldDefinition::string('link_label', __('storefront::texts.hero_block.button_text'), max: 40),
            FieldDefinition::string('link_url', __('storefront::texts.hero_block.button_link'), help: __('storefront::texts.hero_block.path_within_website_thuong_hieu'), max: 300),
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
