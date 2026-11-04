<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Database\Eloquent\Builder;
use Plugin\Cms\Persistence\Post;

final class ArticleLinkType extends ContentLinkType
{
    public function type(): string
    {
        return 'article';
    }

    public function label(): string
    {
        return 'Bài viết';
    }

    protected function query(): Builder
    {
        return Post::query();
    }

    protected function url(string $slug, string $locale): string
    {
        return vani_route('storefront.p.vani-cms.post', $slug, $locale);
    }
}
