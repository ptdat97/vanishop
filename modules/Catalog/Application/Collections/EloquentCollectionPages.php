<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Collections;

use Modules\Catalog\Contracts\CollectionPages;
use Modules\Catalog\Contracts\MediaUrls;
use Modules\Catalog\Persistence\Models\ProductCollection;

final class EloquentCollectionPages implements CollectionPages
{
    public function __construct(private readonly MediaUrls $media) {}

    public function published(string $slug, string $locale): ?array
    {
        $collection = ProductCollection::query()->wherePublishedSlug($slug, $locale)->with('translations')->first();

        return $collection === null ? null : $this->data($collection, $locale);
    }

    public function editable(int $id, string $locale): ?array
    {
        $collection = ProductCollection::query()->with('translations')->find($id);

        return $collection === null ? null : $this->data($collection, $locale);
    }

    public function sitemapSlugs(int $limit): iterable
    {
        foreach (ProductCollection::query()->where('status', 'active')->with('translations')->orderBy('id')->limit($limit)->get(['id', 'slug']) as $collection) {
            yield ['slugs' => $collection->slugs(), 'locales' => $collection->translatedLocales()];
        }
    }

    /** @return array{id: int, slug: string, slugs: array<string, string>, locales: list<string>, name: string, description: string|null, meta_title: string|null, meta_description: string|null, image_url: string|null, image_srcset: string, status: string} */
    private function data(ProductCollection $collection, string $locale): array
    {
        return [
            'id' => $collection->id,
            'slug' => $collection->slugFor($locale),
            'slugs' => $collection->slugs(),
            'locales' => $collection->translatedLocales(),
            'name' => (string) $collection->translate('name', $locale),
            'description' => $collection->translate('description', $locale),
            'meta_title' => $collection->translate('meta_title', $locale),
            'meta_description' => $collection->translate('meta_description', $locale),
            // Ảnh bìa từ Thư viện ảnh (bản thu nhỏ 1600px cho banner đầu trang và ảnh chia sẻ).
            'image_url' => $collection->image_media_id === null ? null : $this->media->url($collection->image_media_id, 1600),
            'image_srcset' => $this->media->srcset($collection->image_media_id),
            'status' => $collection->status,
        ];
    }
}
