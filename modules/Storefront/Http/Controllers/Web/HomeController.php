<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\ProductFilters;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Application\ProductViews;

final class HomeController
{
    public function __invoke(CatalogReader $catalog, ProductViews $products, PageBlocks $pageBlocks): View
    {
        $now = now()->getTimestamp();
        $configured = $pageBlocks->home();

        if ($configured !== null) {
            return view('theme::pages.home', ['blocks' => $pageBlocks->render($configured)]);
        }

        return view('theme::pages.home', [
            'blocks' => null,
            'categories' => $catalog->categoryTree(App::getLocale()),
            'brands' => $catalog->brands(),
            'newest' => $products->listing(new ProductFilters(now: $now, perPage: 8), App::getLocale(), $now)['items'],
        ]);
    }
}
