<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\StorefrontExtras\Infrastructure\Products;

/**
 * Video hero (đặc tả §3.3): video https mp4/webm (máy tính + di động), poster, nội dung phủ, nút. Tự phát luôn tắt tiếng;
 * không tự phát hoặc "giảm chuyển động" → poster + nút phát. URL video sai → không render khối (Core bỏ khối lỗi).
 */
final class VideoHeroBlock implements StorefrontBlock
{
    public function type(): string
    {
        return 'extras_video_hero';
    }

    public function label(): string
    {
        return 'Video hero';
    }

    public function fields(): array
    {
        return [
            FieldDefinition::string('video_url', 'URL video (máy tính)', required: true, help: 'https, đuôi .mp4 hoặc .webm (lưu trên S3/CDN của cửa hàng).', max: 500),
            FieldDefinition::string('video_mobile_url', 'URL video (di động)', help: 'Để trống: dùng video máy tính.', max: 500),
            FieldDefinition::media('poster', 'Ảnh poster', help: 'Từ Thư viện ảnh — hiện khi video chưa phát; nên có để trang tải nhanh.'),
            FieldDefinition::string('title', 'Tiêu đề phủ', max: 120),
            FieldDefinition::text('text', 'Nội dung phủ', max: 300),
            FieldDefinition::string('cta_label', 'Chữ trên nút', max: 40),
            FieldDefinition::string('cta_url', 'Đường dẫn nút', max: 300),
            FieldDefinition::bool('autoplay', 'Tự phát (tắt tiếng)'),
            FieldDefinition::bool('loop', 'Lặp lại'),
        ];
    }

    public function resolve(array $config, string $locale): array
    {
        $video = self::videoUrl($config['video_url'] ?? null) ?? throw new \InvalidArgumentException('URL video phải là https, đuôi .mp4/.webm.');

        return [
            'video' => $video,
            'videoMobile' => self::videoUrl($config['video_mobile_url'] ?? null),
            'poster' => Products::image($config['poster'] ?? null, $config['poster_url'] ?? '', 1600)[0],
            'title' => (string) ($config['title'] ?? ''),
            'text' => (string) ($config['text'] ?? ''),
            'ctaLabel' => (string) ($config['cta_label'] ?? ''),
            'ctaUrl' => Products::safeUrl($config['cta_url'] ?? ''),
            'autoplay' => (bool) ($config['autoplay'] ?? false),
            'loop' => (bool) ($config['loop'] ?? false),
        ];
    }

    public static function videoUrl(mixed $url): ?string
    {
        $url = trim((string) $url);

        return preg_match('#^https://[^\s"<>]+\.(mp4|webm)(\?[^\s"<>]*)?$#i', $url) === 1 ? $url : null;
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.video-hero';
    }
}
