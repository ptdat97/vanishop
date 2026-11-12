<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\BrandIndex;
use Modules\Storefront\Application\ProductViews;
use Modules\Storefront\Application\Seo\PageAlternates;
use Modules\Storefront\Contracts\PageSeo;
use Modules\Storefront\Http\Requests\ProductListRequest;

/**
 * Trang danh sách (PLP): danh mục, thương hiệu, tìm kiếm — cùng ProductViews/ProductFilters với GET /api/storefront/v1/products.
 */
final class CatalogController
{
    public function __construct(
        private readonly CatalogReader $catalog,
        private readonly ProductViews $products,
    ) {}

    public function category(ProductListRequest $request, string $slug, PageSeo $seo): View|RedirectResponse
    {
        $category = $this->catalog->category($slug, App::getLocale());
        abort_if($category === null, 404);
        if ($category['slug'] !== $slug) {
            return redirect()->to(LocalizedUrls::route('storefront.category', [$category['slug'], ...$request->query()]), 301);
        }
        $seo->alternates(PageAlternates::forSlugs('storefront.category', $category['slugs']), $category['locales']);
        $request->merge(['category' => $slug]);

        return view('theme::pages.category', ['category' => $category, ...$this->listing($request)]);
    }

    public function brands(): View
    {
        $brands = $this->catalog->brands();

        // Nhóm theo chữ cái đầu (§3.5-3); `brands` giữ thứ tự hiển thị cho theme cũ.
        return view('theme::pages.brands', ['brands' => $brands, 'brandGroups' => BrandIndex::group($brands)]);
    }

    public function brand(ProductListRequest $request, string $slug): View
    {
        $brand = $this->catalog->brand($slug);
        abort_if($brand === null, 404);
        $request->merge(['brand' => $slug]);

        return view('theme::pages.brand', ['brand' => $brand, ...$this->listing($request)]);
    }

    public function search(ProductListRequest $request): View
    {
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
}
