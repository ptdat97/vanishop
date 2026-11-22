<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CollectionPages;
use Modules\Storefront\Application\Blocks\BlockLayout;
use Modules\Storefront\Application\PageBlocks;

final class CollectionController
{
    public function show(string $slug, CollectionPages $pages, PageBlocks $blocks): JsonResponse
    {
        $page = $pages->published($slug, App::getLocale());
        abort_if($page === null, 404);
        $page['blocks'] = $blocks->resolveZones(BlockLayout::collection(), $page['blocks']);
        unset($page['status']);

        return response()->json(['data' => $page]);
    }
}
