<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\ProductViews;
use Modules\Storefront\Application\Seo\PageAlternates;
use Modules\Storefront\Contracts\PageSeo;
use Modules\Storefront\View\ProductPage;

final class ProductController
{
    public function show(Request $request, string $slug, ProductViews $products, PageSeo $seo, ProductPage $page, CatalogReader $catalog): View|RedirectResponse|Response
    {
        $locale = App::getLocale();
        $now = now()->getTimestamp();
        $product = $products->detail($slug, $locale, $now);
        if ($product === null) {
            // Slug đã đổi ở Admin: link cũ → 301 sang slug mới (giữ query, vd. ?sku=), nếu sản phẩm còn hiển thị.
            $current = $catalog->slugRedirect(CatalogReader::SLUG_PRODUCT, $slug, $locale);
            if ($current !== null && $current !== $slug && $products->detail($current, $locale, $now) !== null) {
                return redirect()->to(LocalizedUrls::route('storefront.product', [$current, ...$request->query()]), 301);
            }

            // Ngừng bán (lưu trữ) → 410 (Google gỡ nhanh khỏi kết quả) + gợi ý cùng danh mục để khách mua tiếp (L44).
            $gone = $catalog->discontinuedProduct($current ?? $slug, $locale);
            abort_if($gone === null, 404);
            $seo->noindex();

            return response()->view('theme::pages.product-gone', [
                'name' => $gone['name'],
                'categoryUrl' => $gone['category_slug'] === null ? null : LocalizedUrls::route('storefront.category', $gone['category_slug']),
                'suggestedProducts' => $products->listing(new ProductFilters(now: $now, categorySlug: $gone['category_slug'], perPage: 8), $locale, $now)['items'],
            ], 410);
        }
        // Trang ngôn ngữ khác mở bằng slug gốc trong khi sản phẩm có slug riêng → 301 sang URL chính (ADR-038 §4).
        if ($product['slug'] !== $slug) {
            return redirect()->to(LocalizedUrls::route('storefront.product', $product['slug']), 301);
        }
        $seo->alternates(PageAlternates::forSlugs('storefront.product', $product['slugs']), $product['locales']);

        $sku = $request->query('sku');

        // `page`: dữ liệu hiển thị dựng sẵn (metadata, breadcrumb, gallery, bộ chọn biến thể) — theme chỉ in ra.
        return view('theme::pages.product', ['product' => $product, 'page' => $page->for($product, is_string($sku) ? $sku : null)]);
    }
}
