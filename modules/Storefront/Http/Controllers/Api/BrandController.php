<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Catalog\Contracts\CatalogReader;

/**
 * Thương hiệu (thuộc tính catalog, ADR-028): danh sách và chi tiết cho trang brand.
 * Sản phẩm của brand: GET /products?brand={slug}.
 */
final class BrandController
{
    public function index(CatalogReader $catalog): JsonResponse
    {
        return response()->json(['data' => $catalog->brands()]);
    }

    public function show(string $slug, CatalogReader $catalog): JsonResponse
    {
        $brand = $catalog->brand($slug);

        abort_if($brand === null, 404, __('Không tìm thấy thương hiệu.'));

        return response()->json(['data' => $brand]);
    }
}
