<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Modules\Catalog\Contracts\VideoDriver;
use Modules\Catalog\Persistence\Models\Video;
use Modules\Extension\Contracts\Extensions;

/**
 * Các VideoDriver đang bật (plugin) theo mã.
 *
 * @phpstan-type VideoView array{id: int, driver: string, reference: string, title: string|null, watch_url: string, embed_url: string|null, thumbnail_url: string|null}
 */
final class VideoDrivers
{
    public function __construct(private readonly Extensions $extensions) {}

    /**
     * @return array<string, VideoDriver>
     */
    public function all(): array
    {
        return $this->extensions->implementations(VideoDriver::TAG, VideoDriver::class);
    }

    public function find(string $code): ?VideoDriver
    {
        return $this->all()[$code] ?? null;
    }

    /**
     * Dữ liệu hiển thị của video; null khi driver của nó không còn bật.
     *
     * @return VideoView|null
     */
    public function view(Video $video): ?array
    {
        $driver = $this->find($video->driver);
        if ($driver === null) {
            return null;
        }

        return [
            'id' => $video->id,
            'driver' => $video->driver,
            'reference' => $video->reference,
            'title' => $video->title,
            'watch_url' => $driver->watchUrl($video->reference),
            'embed_url' => $driver->embedUrl($video->reference),
            'thumbnail_url' => $driver->thumbnail($video->reference)?->url,
        ];
    }
}
