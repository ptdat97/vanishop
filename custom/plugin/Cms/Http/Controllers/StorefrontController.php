<?php

declare(strict_types=1);

namespace Plugin\Cms\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Storefront\Contracts\PageSeo;
use Plugin\Cms\Infrastructure\ContentPresenter;
use Plugin\Cms\Persistence\Category;
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

    public function __construct(private readonly ContentPresenter $presenter, private readonly PageSeo $seo) {}

    public function page(Request $request, string $slug): View
    {
        $page = $this->visible(Page::query(), $request, $slug);
        $this->singleLanguage('storefront.p.vani-cms.page', $slug);

        return view('vani-cms::pages.page', ['page' => $this->presenter->page($page), 'preview' => ! $page->isPublic()]);
    }

    public function blog(Request $request): View
    {
        return $this->listing(null);
    }

    /**
     * /tin-tuc/{slug}: chuyên mục (1.4.0) hoặc bài viết — slug không trùng giữa hai loại (kiểm khi lưu).
     */
    public function post(Request $request, string $slug): View
    {
        $category = Category::query()->where('slug', $slug)->first();
        if ($category !== null) {
            $this->singleLanguage('storefront.p.vani-cms.post', $slug);

            return $this->listing($category);
        }

        $post = $this->visible(Post::query()->with('category'), $request, $slug);
        $this->singleLanguage('storefront.p.vani-cms.post', $slug);

        return view('vani-cms::pages.post', ['post' => $this->presenter->post($post), 'preview' => ! $post->isPublic()]);
    }

    public function apiPage(string $slug): JsonResponse
    {
        $page = Page::query()->published()->where('slug', $slug)->first() ?? abort(404);

        return response()->json(['data' => $this->presenter->page($page)]);
    }

    public function apiPosts(Request $request): JsonResponse
    {
        $category = $request->filled('category') ? Category::query()->where('slug', (string) $request->query('category'))->first() ?? abort(404) : null;
        $posts = Post::query()->published()->with('category')->when($category !== null, fn ($query) => $query->where('category_id', $category->id))
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(min(50, max(1, (int) $request->query('per_page', self::PER_PAGE))));

        return response()->json([
            'data' => $posts->getCollection()->map(fn (Post $post): array => $this->presenter->postSummary($post))->all(),
            'meta' => ['page' => $posts->currentPage(), 'per_page' => $posts->perPage(), 'total' => $posts->total()],
        ]);
    }

    public function apiCategories(): JsonResponse
    {
        return response()->json(['data' => Category::query()->orderBy('sort_order')->orderBy('name')->get()->map(fn (Category $category): array => $this->presenter->category($category))->all()]);
    }

    public function apiPost(string $slug): JsonResponse
    {
        $post = Post::query()->published()->with('category')->where('slug', $slug)->first() ?? abort(404);

        return response()->json(['data' => $this->presenter->post($post)]);
    }

    private function listing(?Category $category): View
    {
        $posts = Post::query()->published()->with('category')->when($category !== null, fn ($query) => $query->where('category_id', $category->id))
            ->orderByDesc('published_at')->orderByDesc('id')->paginate(self::PER_PAGE)->withQueryString();

        return view('vani-cms::pages.blog', [
            'posts' => $posts->getCollection()->map(fn (Post $post): array => $this->presenter->postSummary($post))->all(),
            'paginator' => $posts,
            'category' => $category === null ? null : $this->presenter->category($category),
            'categories' => Category::query()->whereHas('posts', fn ($query) => $query->published())->orderBy('sort_order')->orderBy('name')->get()
                ->map(fn (Category $item): array => $this->presenter->category($item))->all(),
        ]);
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

    /**
     * Nội dung CMS chưa đa ngôn ngữ: chỉ bản ngôn ngữ mặc định được index; bản /en hiện cùng nội dung với `noindex` và
     * canonical về bản mặc định (ADR-038 §5).
     */
    private function singleLanguage(string $route, string $slug): void
    {
        $urls = [];
        foreach ((array) config('vanishop.locale.supported', []) as $locale) {
            $urls[(string) $locale] = vani_route($route, $slug, (string) $locale);
        }
        $this->seo->alternates($urls, [(string) config('vanishop.locale.default')]);
    }
}
