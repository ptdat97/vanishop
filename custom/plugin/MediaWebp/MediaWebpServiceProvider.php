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
        $this->settings([
            ['key' => 'convert_png', 'label' => 'Chuyển cả ảnh PNG', 'type' => 'bool', 'help' => 'Mặc định bật. Tắt nếu ảnh PNG (logo, ảnh nền trong suốt) bị lệch màu sau khi chuyển.'],
            ['key' => 'quality', 'label' => 'Chất lượng WebP (1–100)', 'type' => 'int', 'help' => 'Để trống = theo vanishop.media.cache.quality. Đổi xong chạy php artisan vani:media:cache --clear.'],
        ]);

        $this->contribute(ImageFormat::TAG, WebpFormat::class);
    }
}
