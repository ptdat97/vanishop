<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Modules\Storefront\Contracts\SitemapProvider;
use Plugin\Cms\Persistence\Page;
use Plugin\Cms\Persistence\Post;

final class CmsSitemap implements SitemapProvider
{
    public function urls(int $limit): array
    {
        $urls = Page::query()->published()->orderBy('id')->limit($limit)->pluck('slug')
            ->map(fn (string $slug): string => route('storefront.p.vani-cms.page', $slug))->all();

        if (count($urls) < $limit && Post::query()->published()->exists()) {
            $urls[] = route('storefront.p.vani-cms.blog');
            $posts = Post::query()->published()->orderByDesc('published_at')->limit($limit - count($urls))->pluck('slug');
            foreach ($posts as $slug) {
                $urls[] = route('storefront.p.vani-cms.post', $slug);
            }
        }

        return array_values($urls);
    }
}
