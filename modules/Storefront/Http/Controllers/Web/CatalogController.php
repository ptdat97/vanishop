<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Storefront\Application\ProductViews;
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

    public function category(ProductListRequest $request, string $slug): View
    {
        $category = $this->catalog->category($slug, App::getLocale());
        abort_if($category === null, 404);
        $request->merge(['category' => $slug]);

        return view('theme::pages.category', ['category' => $category, ...$this->listing($request)]);
    }

    public function brands(): View
    {
        return view('theme::pages.brands', ['brands' => $this->catalog->brands()]);
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
            'filters' => $request->only(['q', 'color', 'brand', 'attr', 'sort']),
        ];
    }
}
