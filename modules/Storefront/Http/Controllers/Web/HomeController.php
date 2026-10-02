<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Storefront\Application\ProductViews;

final class HomeController
{
    public function __invoke(CatalogReader $catalog, ProductViews $products): View
    {
        $now = now()->getTimestamp();

        return view('theme::pages.home', [
            'categories' => $catalog->categoryTree(App::getLocale()),
            'brands' => $catalog->brands(),
            'newest' => $products->listing(new ProductFilters(now: $now, perPage: 8), App::getLocale(), $now)['items'],
        ]);
    }
}
