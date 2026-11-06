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
    ) {}

    /** @var array<int, Media|null> ghi nhớ trong request: một trang gọi vani_image() nhiều lần cho cùng ảnh */
    private array $loaded = [];

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
        $missing = array_values(array_diff(array_unique($ids), array_keys($this->loaded)));
        if ($missing !== []) {
            $this->loaded += array_fill_keys($missing, null);
            foreach (Media::query()->whereKey($missing)->get() as $media) {
                $this->loaded[$media->id] = $media;
            }
        }

        return array_filter(array_intersect_key($this->loaded, array_flip($ids)));
    }
}
