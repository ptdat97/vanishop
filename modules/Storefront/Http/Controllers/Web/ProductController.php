<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\App;
use Modules\Storefront\Application\ProductViews;

final class ProductController
{
    public function show(string $slug, ProductViews $products): View
    {
        $product = $products->detail($slug, App::getLocale(), now()->getTimestamp());
        abort_if($product === null, 404);

        return view('theme::pages.product', ['product' => $product]);
    }
}
