<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Contracts\CollectionPages;
use Modules\Storefront\Application\Blocks\BlockLayout;
use Modules\Storefront\Application\Builder\BuilderDrafts;
use Modules\Storefront\Application\Builder\BuilderEngine;

final class BuilderDraftController
{
    public function __construct(private readonly BuilderDrafts $drafts) {}

    public function surface(Request $request, string $surface, string $operation, BuilderEngine $engine): JsonResponse
    {
        Gate::authorize('storefront.manage');
        $definition = $engine->surface($surface) ?? abort(404);

        return $this->tool($request, $engine->layout($definition), $operation);
    }

    public function collection(Request $request, int $collection, string $operation, CollectionPages $pages): JsonResponse
    {
        Gate::authorize('storefront.manage');
        abort_if($pages->editable($collection, App::getLocale()) === null, 404);

        return $this->tool($request, BlockLayout::collection(), $operation);
    }

    private function tool(Request $request, BlockLayout $layout, string $operation): JsonResponse
    {
        return match ($operation) {
            'preview' => $this->drafts->preview($request, $layout),
            'import' => $this->drafts->import($request, $layout),
            default => abort(404),
        };
    }
}
