<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Modules\Shared\Support\StoreClock;
use Plugin\Cms\Persistence\Page;
use Plugin\Cms\Persistence\Post;

/**
 * Dữ liệu trang/bài viết cho storefront (Blade + Storefront API).
 */
final class ContentPresenter
{
    public function __construct(
        private readonly Markdown $markdown,
        private readonly CmsMedia $media,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function page(Page $page): array
    {
        return [
            'slug' => $page->slug, 'title' => $page->title, 'html' => $this->markdown->html($page->body),
            'meta_title' => $page->meta_title ?: $page->title,
            'meta_description' => $page->meta_description ?: $this->markdown->plain($page->body),
            'url' => route('storefront.p.vani-cms.page', $page->slug),
            'updated_at' => $page->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function postSummary(Post $post): array
    {
        return [
            'slug' => $post->slug, 'title' => $post->title,
            'excerpt' => $post->excerpt ?: $this->markdown->plain($post->body, 200),
            'cover_url' => $this->media->coverUrl($post),
            'url' => route('storefront.p.vani-cms.post', $post->slug),
            'published_at' => $post->published_at?->toIso8601String(),
            'published_date' => StoreClock::format($post->published_at, 'date'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function post(Post $post): array
    {
        return [
            ...$this->postSummary($post),
            'html' => $this->markdown->html($post->body),
            'meta_title' => $post->meta_title ?: $post->title,
            'meta_description' => $post->meta_description ?: ($post->excerpt ?: $this->markdown->plain($post->body)),
        ];
    }
}
