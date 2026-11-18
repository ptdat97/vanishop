<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Support\Facades\Cache;
use Plugin\Cms\Persistence\Page;

/**
 * Link trang CMS ở header/footer storefront.
 * Cache 5 phút; lưu/xoá nội dung xoá cache.
 */
final class Navigation
{
    private const CACHE_KEY = 'plg:cms:navigation';

    /**
     * @return array{header: list<array{label: string, url: string}>, footer: list<array{label: string, url: string}>}
     */
    public function links(): array
    {
        /** @var array{header: list<array{label: string, slug: string}>, footer: list<array{label: string, slug: string}>} $data */
        $data = Cache::remember(self::CACHE_KEY, 300, function (): array {
            $pages = Page::query()->published()->where(fn ($query) => $query->where('show_in_header', true)->orWhere('show_in_footer', true))
                ->orderBy('sort_order')->orderBy('title')->get(['title', 'slug', 'show_in_header', 'show_in_footer']);

            return [
                'header' => $pages->where('show_in_header', true)->map(fn (Page $page): array => ['label' => $page->title, 'slug' => $page->slug])->values()->all(),
                'footer' => $pages->where('show_in_footer', true)->map(fn (Page $page): array => ['label' => $page->title, 'slug' => $page->slug])->values()->all(),
            ];
        });

        $url = fn (array $link): array => ['label' => $link['label'], 'url' => vani_route('storefront.p.vani-cms.page', $link['slug'])];
        $header = array_map($url, $data['header']);

        return ['header' => $header, 'footer' => array_map($url, $data['footer'])];
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
