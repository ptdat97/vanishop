<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;

final class HeroBlock implements StorefrontBlock
{
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
            FieldDefinition::string('image_url', 'Ảnh (URL)', max: 500),
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
            'imageUrl' => (string) ($config['image_url'] ?? ''),
            'linkLabel' => (string) ($config['link_label'] ?? ''),
            // Chỉ nhận đường dẫn nội bộ hoặc http(s) — chặn javascript:.
            'linkUrl' => preg_match('#^(/|https?://)#', $link) === 1 ? $link : '',
        ];
    }

    public function view(): string
    {
        return 'theme::blocks.hero';
    }
}
