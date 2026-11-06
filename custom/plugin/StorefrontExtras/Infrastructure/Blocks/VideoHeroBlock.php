<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\StorefrontExtras\Infrastructure\Products;

/**
 * Video hero (đặc tả §3.3): video mp4/webm (máy tính + di động), poster, nội dung phủ, nút. Tự phát luôn tắt tiếng;
 * không tự phát hoặc "giảm chuyển động" → poster + nút phát. 1.2.0 (Core 0.3.91): video chọn từ Thư viện ảnh (id);
 * cấu hình cũ có `video_url`/`video_mobile_url` (https .mp4/.webm) vẫn hiện. Không có video hợp lệ → không render khối.
 */
final class VideoHeroBlock implements StorefrontBlock
{
    public function __construct(private readonly MediaDirectory $media) {}

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
            FieldDefinition::video('video', 'Video (máy tính)', required: true, help: 'Từ Thư viện ảnh — MP4 (H.264) hoặc WebM, nên dưới 10 MB.'),
            FieldDefinition::video('video_mobile', 'Video (di động)', help: 'Để trống: dùng video máy tính. Nên là bản dọc, nhẹ hơn.'),
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
        $videos = $this->media->find(array_values(array_filter([(int) ($config['video'] ?? 0), (int) ($config['video_mobile'] ?? 0)])));
        $source = function (string $mediaKey, string $legacyKey) use ($config, $videos): ?array {
            $media = $videos[(int) ($config[$mediaKey] ?? 0)] ?? null;
            if ($media !== null && $media->isVideo()) {
                return ['src' => $media->url, 'type' => $media->mimeType];
            }
            $url = self::videoUrl($config[$legacyKey] ?? null);

            return $url === null ? null : ['src' => $url, 'type' => str_ends_with(strtolower((string) strtok($url, '?')), '.webm') ? 'video/webm' : 'video/mp4'];
        };
        $video = $source('video', 'video_url') ?? throw new \InvalidArgumentException('Chưa chọn video (MP4/WebM) từ Thư viện ảnh.');

        return [
            'video' => $video,
            'videoMobile' => $source('video_mobile', 'video_mobile_url'),
            'poster' => Products::image($config['poster'] ?? null, $config['poster_url'] ?? '', 1600)[0],
            'title' => (string) ($config['title'] ?? ''),
            'text' => (string) ($config['text'] ?? ''),
            'ctaLabel' => (string) ($config['cta_label'] ?? ''),
            'ctaUrl' => Products::safeUrl($config['cta_url'] ?? ''),
            'autoplay' => (bool) ($config['autoplay'] ?? false),
            'loop' => (bool) ($config['loop'] ?? false),
        ];
    }

    /**
     * URL video nhập tay của cấu hình cũ (trước 1.2.0): chỉ https, đuôi .mp4/.webm.
     */
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
