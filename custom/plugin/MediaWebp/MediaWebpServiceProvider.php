<?php

declare(strict_types=1);

namespace Plugin\MediaWebp;

use Modules\Catalog\Contracts\ImageFormat;
use Modules\Extension\PluginServiceProvider;
use Plugin\MediaWebp\Infrastructure\WebpFormat;

/**
 * Ảnh thu nhỏ trong public/cache xuất ra WebP thay vì giữ định dạng gốc (ImageFormat, Catalog 0.3.28).
 * Ảnh gốc không bị đụng tới; tắt plugin → URL quay về định dạng gốc.
 */
final class MediaWebpServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.media-webp';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-media-webp');
        $this->settings([
            ['key' => 'convert_png', 'label' => 'vani-media-webp::texts.media_webp_service_provider.also_convert_png_images', 'type' => 'bool', 'help' => 'vani-media-webp::texts.media_webp_service_provider.default_turn_off_if_png'],
            ['key' => 'quality', 'label' => 'vani-media-webp::texts.media_webp_service_provider.webp_quality_1_100', 'type' => 'int', 'help' => 'vani-media-webp::texts.media_webp_service_provider.leave_empty_use_vanishop_media'],
        ]);

        $this->contribute(ImageFormat::TAG, WebpFormat::class);
    }
}
