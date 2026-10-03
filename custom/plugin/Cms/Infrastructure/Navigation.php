<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Support\Facades\Cache;
use Plugin\Cms\Persistence\Page;
use Plugin\Cms\Persistence\Post;

/**
 * Link nội dung ở header/footer storefront (trang đánh dấu hiện ở header/footer, "Tin tức" khi đã có bài).
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
        /** @var array{header: list<array{label: string, slug: string}>, footer: list<array{label: string, slug: string}>, blog: bool} $data */
        $data = Cache::remember(self::CACHE_KEY, 300, function (): array {
            $pages = Page::query()->published()->where(fn ($query) => $query->where('show_in_header', true)->orWhere('show_in_footer', true))
                ->orderBy('sort_order')->orderBy('title')->get(['title', 'slug', 'show_in_header', 'show_in_footer']);

            return [
                'header' => $pages->where('show_in_header', true)->map(fn (Page $page): array => ['label' => $page->title, 'slug' => $page->slug])->values()->all(),
                'footer' => $pages->where('show_in_footer', true)->map(fn (Page $page): array => ['label' => $page->title, 'slug' => $page->slug])->values()->all(),
                'blog' => Post::query()->published()->exists(),
            ];
        });

        $url = fn (array $link): array => ['label' => $link['label'], 'url' => route('storefront.p.vani-cms.page', $link['slug'])];
        $header = array_map($url, $data['header']);
        if ($data['blog']) {
            $header[] = ['label' => 'Tin tức', 'url' => route('storefront.p.vani-cms.blog')];
        }

        return ['header' => $header, 'footer' => array_map($url, $data['footer'])];
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
