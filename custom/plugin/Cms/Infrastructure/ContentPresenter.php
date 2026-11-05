<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Support\Facades\App;
use Modules\Shared\Support\StoreClock;
use Modules\Storefront\Contracts\ProductCards;
use Plugin\Cms\Persistence\Category;
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
        private readonly ProductCards $products,
    ) {}

    /**
     * @return array{slug: string, name: string, description: ?string, url: string, meta_title: string, meta_description: ?string}
     */
    public function category(Category $category): array
    {
        return [
            'slug' => $category->slug, 'name' => $category->name, 'description' => $category->description,
            'url' => vani_route('storefront.p.vani-cms.post', $category->slug),
            'meta_title' => $category->meta_title ?: $category->name, 'meta_description' => $category->meta_description ?: $category->description,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function page(Page $page): array
    {
        return [
            'slug' => $page->slug, 'title' => $page->title, 'html' => $this->markdown->html($page->body),
            'meta_title' => $page->meta_title ?: $page->title,
            'meta_description' => $page->meta_description ?: $this->markdown->plain($page->body),
            'url' => vani_route('storefront.p.vani-cms.page', $page->slug),
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
            'url' => vani_route('storefront.p.vani-cms.post', $post->slug),
            'published_at' => $post->published_at?->toIso8601String(),
            'published_date' => StoreClock::format($post->published_at, 'date'),
            'category' => $post->category === null ? null : ['slug' => $post->category->slug, 'name' => $post->category->name, 'url' => vani_route('storefront.p.vani-cms.post', $post->category->slug)],
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
            // Sản phẩm gắn cuối bài (1.4.0): thẻ sản phẩm của Core, mẫu ẩn/ngừng bán tự bỏ.
            'products' => ($ids = array_slice(array_map('intval', (array) $post->product_ids), 0, Post::MAX_PRODUCTS)) === [] ? [] : $this->products->forIds($ids, App::getLocale()),
        ];
    }
}
