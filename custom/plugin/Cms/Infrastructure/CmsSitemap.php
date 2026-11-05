<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Modules\Storefront\Contracts\SitemapProvider;
use Plugin\Cms\Persistence\Category;
use Plugin\Cms\Persistence\Page;
use Plugin\Cms\Persistence\Post;

final class CmsSitemap implements SitemapProvider
{
    public function urls(int $limit): array
    {
        $urls = Page::query()->published()->orderBy('id')->limit($limit)->pluck('slug')
            ->map(fn (string $slug): string => vani_route('storefront.p.vani-cms.page', $slug))->all();

        if (count($urls) < $limit && Post::query()->published()->exists()) {
            $urls[] = vani_route('storefront.p.vani-cms.blog');
            // Chuyên mục có bài công khai (1.4.0).
            foreach (Category::query()->whereHas('posts', fn ($query) => $query->published())->orderBy('sort_order')->limit($limit - count($urls))->pluck('slug') as $slug) {
                $urls[] = vani_route('storefront.p.vani-cms.post', $slug);
            }
            $posts = Post::query()->published()->orderByDesc('published_at')->limit(max(0, $limit - count($urls)))->pluck('slug');
            foreach ($posts as $slug) {
                $urls[] = vani_route('storefront.p.vani-cms.post', $slug);
            }
        }

        return array_values($urls);
    }
}
