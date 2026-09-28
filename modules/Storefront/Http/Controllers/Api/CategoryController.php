<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CatalogReader;

final class CategoryController
{
    public function index(CatalogReader $catalog): JsonResponse
    {
        return response()->json(['data' => $catalog->categoryTree(App::getLocale())]);
    }

    public function show(string $slug, CatalogReader $catalog): JsonResponse
    {
        $category = $catalog->category($slug, App::getLocale());

        abort_if($category === null, 404, __('Không tìm thấy danh mục.'));

        return response()->json(['data' => $category]);
    }
}
