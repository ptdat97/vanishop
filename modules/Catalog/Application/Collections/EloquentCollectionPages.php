<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Collections;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\CollectionPages;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Persistence\Models\ProductCollection;

final class EloquentCollectionPages implements CollectionPages
{
    public function __construct(private readonly MediaDirectory $media) {}

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

    public function save(int $id, array $blocks, array $mediaIds): void
    {
        DB::transaction(function () use ($id, $blocks, $mediaIds): void {
            $collection = ProductCollection::query()->lockForUpdate()->findOrFail($id);
            $collection->page_blocks = $blocks;
            $collection->save();
            $this->media->syncUsages(self::MEDIA_OWNER, $id, 'blocks', $mediaIds);
        });
    }

    /** @return array<string, mixed> */
    private function data(ProductCollection $collection, string $locale): array
    {
        return [
            'id' => $collection->id,
            'slug' => $collection->slugFor($locale),
            'slugs' => $collection->slugs(),
            'locales' => $collection->translatedLocales(),
            'name' => (string) $collection->translate('name', $locale),
            'description' => $collection->translate('description', $locale),
            'status' => $collection->status,
            'blocks' => $collection->page_blocks ?? [],
        ];
    }
}
