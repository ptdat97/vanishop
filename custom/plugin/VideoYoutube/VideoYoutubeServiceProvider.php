<?php

declare(strict_types=1);

namespace Plugin\VideoYoutube;

use Modules\Catalog\Contracts\VideoDriver;
use Modules\Extension\PluginServiceProvider;
use Plugin\VideoYoutube\Infrastructure\YoutubeDriver;

/**
 * Video sản phẩm từ YouTube (VideoDriver, Catalog 0.3.46). Nhúng qua youtube-nocookie.com (không đặt cookie trước khi
 * khách bấm xem).
 */
final class VideoYoutubeServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.video-youtube';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->contribute(VideoDriver::TAG, YoutubeDriver::class);
    }
}
