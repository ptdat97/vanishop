<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Application\ProductViews;
use Modules\Storefront\Http\Requests\ProductListRequest;

final class ProductController
{
    public function index(ProductListRequest $request, ProductViews $products, CurrentContext $context): JsonResponse
    {
        $now = now()->getTimestamp();
        $listing = $products->listing($request->toFilters($context->brandIds() ?? [], $now), App::getLocale(), $now);

        return response()->json([
            'data' => $listing['items'],
            'meta' => ['total' => $listing['total'], 'page' => $listing['page'], 'per_page' => $listing['per_page'], 'facets' => $listing['facets']],
        ]);
    }

    public function show(string $slug, ProductViews $products): JsonResponse
    {
        $product = $products->detail($slug, App::getLocale(), now()->getTimestamp());

        abort_if($product === null, 404, __('Không tìm thấy sản phẩm.'));

        return response()->json(['data' => $product]);
    }
}
