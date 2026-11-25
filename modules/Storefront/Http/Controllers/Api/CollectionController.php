<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CollectionPages;

final class CollectionController
{
    /**
     * Thông tin trang bộ sưu tập (tên, mô tả, SEO, ảnh bìa); sản phẩm lấy qua `GET /products?collection={slug}`.
     */
    public function show(string $slug, CollectionPages $pages): JsonResponse
    {
        $page = $pages->published($slug, App::getLocale());
        abort_if($page === null, 404);
        unset($page['status']);

        return response()->json(['data' => $page]);
    }
}
