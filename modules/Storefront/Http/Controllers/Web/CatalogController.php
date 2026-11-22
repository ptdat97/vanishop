<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\CollectionPages;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\BrandIndex;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Application\ProductViews;
use Modules\Storefront\Application\Seo\PageAlternates;
use Modules\Storefront\Contracts\PageSeo;
use Modules\Storefront\Http\Requests\ProductListRequest;
use Modules\Storefront\View\Breadcrumbs;
use Modules\Storefront\View\PageMeta;

/**
 * Trang danh sách (PLP): danh mục, thương hiệu, tìm kiếm — cùng ProductViews/ProductFilters với GET /api/storefront/v1/products.
 */
final class CatalogController
{
    public function __construct(
        private readonly CatalogReader $catalog,
        private readonly ProductViews $products,
    ) {}

    public function categories(PageBlocks $pageBlocks): View
    {
        $locale = App::getLocale();
        $categoryTree = $this->catalog->categoryTree($locale);
        $categories = [];
        $flatten = function (array $nodes, ?string $parentName = null) use (&$flatten, &$categories): void {
            foreach ($nodes as $node) {
                $categories[] = [
                    'slug' => (string) $node['slug'],
                    'name' => (string) $node['name'],
                    'parent_name' => $parentName,
                    'image_url' => $node['image_url'] ?? null,
                ];
                $flatten((array) ($node['children'] ?? []), (string) $node['name']);
            }
        };
        $flatten($categoryTree);

        $heroBlock = collect($pageBlocks->home() ?? [])->firstWhere('type', 'hero');
        $banner = $heroBlock === null ? [] : ($pageBlocks->resolve([$heroBlock])[0]['data'] ?? []);
        $now = now()->getTimestamp();

        return view('theme::pages.categories', [
            'categories' => $categories,
            'bannerImage' => $banner['imageUrl'] ?? null,
            'bannerImageSrcset' => $banner['imageSrcset'] ?? null,
            'suggestedProducts' => $this->products->listing(new ProductFilters(now: $now, perPage: 8), $locale, $now)['items'],
        ]);
    }

    public function category(ProductListRequest $request, string $slug, PageSeo $seo): View|RedirectResponse
    {
        $category = $this->catalog->category($slug, App::getLocale());
        if ($category === null) {
            return $this->movedSlug(CatalogReader::SLUG_CATEGORY, $slug, 'storefront.category', $request, fn (string $current): bool => $this->catalog->category($current, App::getLocale()) !== null);
        }
        if ($category['slug'] !== $slug) {
            return redirect()->to(LocalizedUrls::route('storefront.category', [$category['slug'], ...$request->query()]), 301);
        }
        $seo->alternates(PageAlternates::forSlugs('storefront.category', $category['slugs']), $category['locales']);
        $request->merge(['category' => $slug]);

        $crumbs = Breadcrumbs::categories($category['breadcrumb'] ?? [['name' => $category['name'], 'slug' => $category['slug']]]);

        return view('theme::pages.category', [
            'category' => $category,
            'crumbs' => $crumbs,
            // Tiêu đề/mô tả/ảnh chia sẻ có dự phòng (PageMeta): meta nhập ở Admin → tên/đoạn đầu mô tả.
            'meta' => PageMeta::for($category, $category['image_url'] ?? null),
            ...$this->listing($request),
        ]);
    }

    public function collection(ProductListRequest $request, string $slug, CollectionPages $pages, PageBlocks $blocks, PageSeo $seo): View|RedirectResponse
    {
        $collection = $pages->published($slug, App::getLocale());
        abort_if($collection === null, 404);
        if ($collection['slug'] !== $slug) {
            return redirect()->to(LocalizedUrls::route('storefront.collection', ['slug' => $collection['slug']] + $request->query()), 301);
        }
        $seo->alternates(PageAlternates::forSlugs('storefront.collection', $collection['slugs']), $collection['locales']);
        $request->merge(['collection' => $slug]);
        $zones = PageBlocks::collectionZones($collection['blocks']);
        $banner = $blocks->resolve($zones['banner'])[0]['data'] ?? [];
        unset($zones['banner']);
        unset($collection['blocks'], $collection['status']);

        return view('theme::pages.collection', [
            'collection' => $collection,
            'crumbs' => [['label' => $collection['name']]],
            'meta' => PageMeta::for($collection, ($banner['imageUrl'] ?? '') ?: null),
            'banner' => $banner,
            'zones' => array_map($blocks->render(...), $zones),
            ...$this->listing($request),
        ]);
    }

    public function brands(): View
    {
        $brands = $this->catalog->brands();

        // Nhóm theo chữ cái đầu (§3.5-3); `brands` giữ thứ tự hiển thị cho theme cũ.
        return view('theme::pages.brands', ['brands' => $brands, 'brandGroups' => BrandIndex::group($brands)]);
    }

    public function brand(ProductListRequest $request, string $slug, PageSeo $seo): View|RedirectResponse
    {
        $brand = $this->catalog->brand($slug);
        if ($brand === null) {
            return $this->movedSlug(CatalogReader::SLUG_BRAND, $slug, 'storefront.brand', $request, fn (string $current): bool => $this->catalog->brand($current) !== null);
        }
        // Thương hiệu một slug cho mọi ngôn ngữ: canonical là URL thương hiệu, không kèm bộ lọc/sắp xếp.
        $seo->alternates(PageAlternates::forSlugs('storefront.brand', array_fill_keys(StoreLocale::supported(), $slug)), StoreLocale::supported());
        $request->merge(['brand' => $slug]);

        $crumbs = [['label' => __('storefront::labels.brands'), 'url' => LocalizedUrls::route('storefront.brands')], ['label' => (string) $brand['name']]];

        return view('theme::pages.brand', ['brand' => $brand, 'crumbs' => $crumbs, 'meta' => PageMeta::for($brand, $brand['logo_url'] ?? null), ...$this->listing($request)]);
    }

    public function search(ProductListRequest $request, PageSeo $seo): View
    {
        // Kết quả tìm kiếm nội bộ không cho Google index (vô số URL theo từ khoá); link trong trang vẫn được đi theo.
        $seo->noindex();

        return view('theme::pages.search', ['query' => (string) $request->input('q', ''), ...$this->listing($request)]);
    }

    /**
     * @return array{listing: array<string, mixed>, filters: array<string, mixed>}
     */
    private function listing(ProductListRequest $request): array
    {
        $now = now()->getTimestamp();

        return [
            'listing' => $this->products->listing($request->toFilters($now), App::getLocale(), $now),
            'filters' => $request->only(['q', 'brand', 'opt', 'price_min', 'price_max', 'attr', 'sort']),
        ];
    }

    /**
     * Slug đã đổi ở Admin: link cũ → 301 sang slug mới (giữ query: bộ lọc, trang) nếu bản ghi còn hiển thị; không thì 404.
     *
     * @param  Closure(string): bool  $visible
     */
    private function movedSlug(string $subject, string $slug, string $route, Request $request, Closure $visible): RedirectResponse
    {
        $current = $this->catalog->slugRedirect($subject, $slug, App::getLocale());
        abort_if($current === null || $current === $slug || ! $visible($current), 404);

        return redirect()->to(LocalizedUrls::route($route, [$current, ...$request->query()]), 301);
    }
}
