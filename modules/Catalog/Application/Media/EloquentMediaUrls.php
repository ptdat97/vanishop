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
    ) {}

    public function url(int $mediaId, ?int $width = null): ?string
    {
        return $this->urls([$mediaId], $width)[$mediaId] ?? null;
    }

    public function urls(array $mediaIds, ?int $width = null): array
    {
        $urls = [];
        foreach (Media::query()->whereKey(array_values(array_unique($mediaIds)))->get() as $media) {
            $urls[$media->id] = $this->cache->url($media, $width);
        }

        return $urls;
    }

    public function image(?int $mediaId, ?int $width = null): ?string
    {
        $url = $mediaId === null ? null : $this->url($mediaId, $width);
        if ($url !== null) {
            return $url;
        }

        $placeholder = $this->settings->get('core', self::PLACEHOLDER_SETTING);

        return is_numeric($placeholder) && (int) $placeholder > 0 ? $this->url((int) $placeholder, $width) : null;
    }
}
