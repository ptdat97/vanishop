<?php

declare(strict_types=1);

namespace Plugin\Lookbook\Infrastructure;

use Illuminate\Support\Str;
use Modules\Catalog\Contracts\CatalogLookup;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Shared\Support\StoreLocale;
use Plugin\Lookbook\Persistence\Lookbook;

/**
 * Dữ liệu lookbook cho storefront (Blade + Storefront API) theo ngôn ngữ, ảnh tra lại URL lúc hiển thị (theo lô).
 */
final class LookbookPresenter
{
    public function __construct(
        private readonly MediaDirectory $media,
        private readonly CatalogLookup $catalog,
    ) {}

    /**
     * @param  list<Lookbook>  $lookbooks
     * @return list<array{slug: string, name: string, cover_url: ?string, url: string, images_count: int}>
     */
    public function summaries(array $lookbooks, string $locale): array
    {
        $covers = $this->media->find(array_values(array_filter(array_map(fn (Lookbook $lookbook): ?int => $this->coverId($lookbook), $lookbooks))));

        return array_map(fn (Lookbook $lookbook): array => [
            'slug' => $lookbook->slug, 'name' => (string) $lookbook->text('name', $locale),
            // Kích thước có tên (Core 0.3.87): bìa cắt khung 2:3, chỉnh được ở Admin → Kích thước ảnh.
            'cover_url' => ($id = $this->coverId($lookbook)) !== null && isset($covers[$id]) ? vani_image($id, LookbookImageSizes::COVER) : null,
            'url' => vani_route('storefront.p.vani-lookbook.show', $lookbook->slug, $locale),
            'images_count' => $lookbook->images->count(),
        ], $lookbooks);
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(Lookbook $lookbook, string $locale): array
    {
        $ids = [...$lookbook->images->pluck('media_id')->all(), ...($lookbook->cover_media_id === null ? [] : [$lookbook->cover_media_id])];
        $media = $ids === [] ? [] : $this->media->find(array_values(array_unique($ids)));
        $name = (string) $lookbook->text('name', $locale);
        $description = $lookbook->text('description', $locale);
        $category = $lookbook->category_id === null ? null : ($this->catalog->visible('category', [$lookbook->category_id], $locale)[$lookbook->category_id] ?? null);
        $cover = $media[$this->coverId($lookbook) ?? 0] ?? null;

        return [
            'slug' => $lookbook->slug, 'name' => $name,
            'description_html' => $description === null ? null : Str::markdown($description, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
            'meta_title' => $lookbook->text('meta_title', $locale) ?? $name,
            'meta_description' => $lookbook->text('meta_description', $locale) ?? ($description === null ? null : Str::limit(strip_tags($description), 160)),
            'cover_url' => $cover?->urlFor(1600),
            'images' => $lookbook->images->filter(fn ($image): bool => isset($media[$image->media_id]))->map(fn ($image): array => [
                'url' => $media[$image->media_id]->urlFor(800), 'full_url' => $media[$image->media_id]->url,
                'srcset' => $media[$image->media_id]->srcset() ?: null,
                'alt' => (string) ($image->alt[$locale] ?? $image->alt[StoreLocale::default()] ?? $name),
                'width' => $media[$image->media_id]->width, 'height' => $media[$image->media_id]->height,
            ])->values()->all(),
            // Danh mục gắn đã ẩn/xoá → không có link (tiêu chí §8.6).
            'category' => $category === null ? null : ['name' => $category['name'], 'url' => vani_route('storefront.category', $category['slug'], $locale)],
            'url' => vani_route('storefront.p.vani-lookbook.show', $lookbook->slug, $locale),
        ];
    }

    /** Ảnh bìa; không chọn → ảnh đầu tiên. */
    private function coverId(Lookbook $lookbook): ?int
    {
        return $lookbook->cover_media_id ?? $lookbook->images->first()?->media_id;
    }
}
