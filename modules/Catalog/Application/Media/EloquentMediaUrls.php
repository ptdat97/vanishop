<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Media;

use Modules\Catalog\Contracts\MediaUrls;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Tenancy\Contracts\Settings;

final class EloquentMediaUrls implements MediaUrls
{
    public const PLACEHOLDER_SETTING = 'storefront.placeholder_image';

    public function __construct(
        private readonly ImageCache $cache,
        private readonly Settings $settings,
        private readonly ImageSizes $sizes,
        private readonly MediaModels $models,
    ) {}

    public function url(int $mediaId, int|string|null $size = null, ?string $group = null): ?string
    {
        return $this->urls([$mediaId], $size, $group)[$mediaId] ?? null;
    }

    public function urls(array $mediaIds, int|string|null $size = null, ?string $group = null): array
    {
        $urls = [];
        foreach ($this->load($mediaIds) as $id => $media) {
            $urls[$id] = $this->cache->url($media, $size, $group);
        }

        return $urls;
    }

    public function image(?int $mediaId, int|string|null $size = null, ?string $group = null): ?string
    {
        $url = $mediaId === null ? null : $this->url($mediaId, $size, $group);
        if ($url !== null) {
            return $url;
        }

        $placeholder = $this->settings->get('core', self::PLACEHOLDER_SETTING);

        return is_numeric($placeholder) && (int) $placeholder > 0 ? $this->url((int) $placeholder, $size, $group) : null;
    }

    public function srcset(?int $mediaId, ?string $group = null): string
    {
        $media = $mediaId === null ? null : ($this->load([$mediaId])[$mediaId] ?? null);

        return $media === null ? '' : $this->cache->srcset($media, $group);
    }

    public function productRatio(): ?array
    {
        return $this->sizes->productRatio();
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, Media>
     */
    private function load(array $ids): array
    {
        // Ghi nhớ trong request, dùng chung với MediaDirectory: một trang gọi vani_image() nhiều lần cho cùng ảnh.
        return $this->models->get($ids);
    }

    public function poster(int $videoMediaId, int|string|null $size = null, ?string $group = null): ?string
    {
        return Media::query()->with('poster')->find($videoMediaId)?->posterUrl($size, $group);
    }
}
