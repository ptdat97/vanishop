<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Contracts\MediaUrls;
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
            FieldDefinition::video('video', __('vani-storefront-extras::texts.video_hero_block.video_desktop'), required: true, help: __('vani-storefront-extras::texts.video_hero_block.from_media_library_mp4_h')),
            FieldDefinition::video('video_mobile', __('vani-storefront-extras::texts.video_hero_block.video_mobile'), help: __('vani-storefront-extras::texts.video_hero_block.leave_empty_use_desktop_video')),
            FieldDefinition::media('poster', __('vani-storefront-extras::texts.video_hero_block.poster_image'), help: __('vani-storefront-extras::texts.video_hero_block.from_media_library_shown_before')),
            FieldDefinition::string('title', __('vani-storefront-extras::texts.video_hero_block.overlay_title'), max: 120),
            FieldDefinition::text('text', __('vani-storefront-extras::texts.video_hero_block.overlay_text'), max: 300),
            FieldDefinition::string('cta_label', __('vani-storefront-extras::texts.video_hero_block.button_text'), max: 40),
            FieldDefinition::string('cta_url', __('vani-storefront-extras::texts.video_hero_block.button_link'), max: 300),
            FieldDefinition::bool('autoplay', __('vani-storefront-extras::texts.video_hero_block.autoplay_muted')),
            FieldDefinition::bool('loop', __('vani-storefront-extras::texts.video_hero_block.loop')),
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
            // Chưa chọn poster → ảnh poster của video Thư viện (Core chụp khung hình khi tải lên, 1.7.0).
            'poster' => Products::image($config['poster'] ?? null, $config['poster_url'] ?? '', 1600)[0]
                ?: (is_numeric($config['video'] ?? null) ? (string) app(MediaUrls::class)->poster((int) $config['video'], 1600) : ''),
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
