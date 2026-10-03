<?php

declare(strict_types=1);

namespace Plugin\Cms\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Plugin\Cms\Infrastructure\ContentPresenter;
use Plugin\Cms\Persistence\Content;
use Plugin\Cms\Persistence\Page;
use Plugin\Cms\Persistence\Post;

/**
 * Storefront: trang nội dung (/trang/{slug}), tin tức (/tin-tuc, /tin-tuc/{slug}) và Storefront API cho headless.
 * Bản nháp chỉ xem được qua link ký từ Admin (noindex).
 */
final class StorefrontController
{
    private const PER_PAGE = 12;

    public function __construct(private readonly ContentPresenter $presenter) {}

    public function page(Request $request, string $slug): View
    {
        $page = $this->visible(Page::query(), $request, $slug);

        return view('vani-cms::pages.page', ['page' => $this->presenter->page($page), 'preview' => ! $page->isPublic()]);
    }

    public function blog(Request $request): View
    {
        $posts = Post::query()->published()->orderByDesc('published_at')->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();

        return view('vani-cms::pages.blog', [
            'posts' => $posts->getCollection()->map(fn (Post $post): array => $this->presenter->postSummary($post))->all(),
            'paginator' => $posts,
        ]);
    }

    public function post(Request $request, string $slug): View
    {
        $post = $this->visible(Post::query(), $request, $slug);

        return view('vani-cms::pages.post', ['post' => $this->presenter->post($post), 'preview' => ! $post->isPublic()]);
    }

    public function apiPage(string $slug): JsonResponse
    {
        $page = Page::query()->published()->where('slug', $slug)->first() ?? abort(404);

        return response()->json(['data' => $this->presenter->page($page)]);
    }

    public function apiPosts(Request $request): JsonResponse
    {
        $posts = Post::query()->published()->orderByDesc('published_at')->orderByDesc('id')->paginate(min(50, max(1, (int) $request->query('per_page', self::PER_PAGE))));

        return response()->json([
            'data' => $posts->getCollection()->map(fn (Post $post): array => $this->presenter->postSummary($post))->all(),
            'meta' => ['page' => $posts->currentPage(), 'per_page' => $posts->perPage(), 'total' => $posts->total()],
        ]);
    }

    public function apiPost(string $slug): JsonResponse
    {
        $post = Post::query()->published()->where('slug', $slug)->first() ?? abort(404);

        return response()->json(['data' => $this->presenter->post($post)]);
    }

    /**
     * @template T of Content
     *
     * @param  Builder<T>  $query
     * @return T
     */
    private function visible($query, Request $request, string $slug): Content
    {
        $item = $query->where('slug', $slug)->first();
        abort_if($item === null || (! $item->isPublic() && ! $request->hasValidSignature()), 404);

        return $item;
    }
}
