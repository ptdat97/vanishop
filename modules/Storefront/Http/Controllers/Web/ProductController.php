<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\ProductViews;
use Modules\Storefront\Application\Seo\PageAlternates;
use Modules\Storefront\Contracts\PageSeo;
use Modules\Storefront\View\ProductPage;

final class ProductController
{
    public function show(Request $request, string $slug, ProductViews $products, PageSeo $seo, ProductPage $page, CatalogReader $catalog): View|RedirectResponse
    {
        $product = $products->detail($slug, App::getLocale(), now()->getTimestamp());
        if ($product === null) {
            // Slug đã đổi ở Admin: link cũ → 301 sang slug mới (giữ query, vd. ?sku=), nếu sản phẩm còn hiển thị.
            $current = $catalog->slugRedirect(CatalogReader::SLUG_PRODUCT, $slug, App::getLocale());
            abort_if($current === null || $current === $slug || $products->detail($current, App::getLocale(), now()->getTimestamp()) === null, 404);

            return redirect()->to(LocalizedUrls::route('storefront.product', [$current, ...$request->query()]), 301);
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
