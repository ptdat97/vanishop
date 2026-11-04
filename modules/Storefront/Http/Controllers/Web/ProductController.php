<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\ProductViews;
use Modules\Storefront\Application\Seo\PageAlternates;
use Modules\Storefront\Contracts\PageSeo;

final class ProductController
{
    public function show(string $slug, ProductViews $products, PageSeo $seo): View|RedirectResponse
    {
        $product = $products->detail($slug, App::getLocale(), now()->getTimestamp());
        abort_if($product === null, 404);
        // Trang ngôn ngữ khác mở bằng slug gốc trong khi sản phẩm có slug riêng → 301 sang URL chính (ADR-038 §4).
        if ($product['slug'] !== $slug) {
            return redirect()->to(LocalizedUrls::route('storefront.product', $product['slug']), 301);
        }
        $seo->alternates(PageAlternates::forSlugs('storefront.product', $product['slugs']), $product['locales']);

        return view('theme::pages.product', ['product' => $product]);
    }
}
