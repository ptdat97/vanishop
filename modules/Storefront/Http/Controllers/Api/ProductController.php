<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\ProductViews;
use Modules\Storefront\Http\Requests\ProductListRequest;

final class ProductController
{
    public function index(ProductListRequest $request, ProductViews $products): JsonResponse
    {
        $ids = $request->styleIds();
        if ($ids !== null) {
            $items = $products->forIds($ids, App::getLocale());

            return response()->json(['data' => $items, 'meta' => ['total' => count($items), 'page' => 1, 'per_page' => count($items), 'facets' => null, 'filters' => []]]);
        }

        $now = now()->getTimestamp();
        $listing = $products->listing($request->toFilters($now), App::getLocale(), $now);

        return response()->json([
            'data' => $listing['items'],
            'meta' => ['total' => $listing['total'], 'page' => $listing['page'], 'per_page' => $listing['per_page'], 'facets' => [...$listing['facets'], 'color_families' => (object) $listing['facets']['color_families']], 'filters' => $listing['filters'] ?? []],
        ]);
    }

    /**
     * Gợi ý khi gõ ô tìm kiếm (§3.5-4): `?q=` (≥ 2 ký tự) → sản phẩm (không giá), danh mục, thương hiệu kèm URL storefront
     * theo ngôn ngữ của request. Không cần đăng nhập.
     */
    public function suggestions(Request $request, CatalogReader $catalog): JsonResponse
    {
        $data = $request->validate(['q' => ['required', 'string', 'max:100'], 'limit' => ['nullable', 'integer', 'min:1', 'max:10']]);
        $locale = App::getLocale();
        $found = $catalog->suggestions((string) $data['q'], $locale, now()->getTimestamp(), (int) ($data['limit'] ?? 5));

        return response()->json(['data' => [
            'products' => array_map(fn (array $item): array => [...$item, 'url' => LocalizedUrls::route('storefront.product', $item['slug'], $locale)], $found['products']),
            'categories' => array_map(fn (array $item): array => [...$item, 'url' => LocalizedUrls::route('storefront.category', $item['slug'], $locale)], $found['categories']),
            'brands' => array_map(fn (array $item): array => [...$item, 'url' => LocalizedUrls::route('storefront.brand', $item['slug'], $locale)], $found['brands']),
            'search_url' => LocalizedUrls::route('storefront.search', ['q' => (string) $data['q']], $locale),
        ]]);
    }

    public function show(string $slug, ProductViews $products): JsonResponse
    {
        $product = $products->detail($slug, App::getLocale(), now()->getTimestamp());

        abort_if($product === null, 404, __('Không tìm thấy sản phẩm.'));

        return response()->json(['data' => $product]);
    }
}
