<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Storefront\Application\Blocks\BlockLayout;
use Modules\Storefront\Application\Builder\BuilderDrafts;
use Modules\Storefront\Application\Builder\BuilderEngine;
use Modules\Storefront\Contracts\ScopedBuilderSurface;

final class BuilderDraftController
{
    public function __construct(private readonly BuilderDrafts $drafts) {}

    public function surface(Request $request, string $surface, string $operation, BuilderEngine $engine): JsonResponse
    {
        Gate::authorize('storefront.manage');
        $definition = $engine->surface($surface);
        abort_if($definition === null || $definition instanceof ScopedBuilderSurface, 404);

        return $this->tool($request, $engine->layout($definition), $operation);
    }

    /**
     * Xem trước/nhập nháp của một phạm vi: bản xem trước dùng ngữ cảnh trang của chính đối tượng.
     */
    public function scope(Request $request, string $surface, string $scope, string $operation, BuilderEngine $engine): JsonResponse
    {
        Gate::authorize('storefront.manage');
        $definition = $engine->surface($surface);
        abort_if(! $definition instanceof ScopedBuilderSurface, 404);
        $described = $definition->describeScope($scope);
        abort_if($described === null, 404);
        if ($described->listingType !== null) {
            $request->merge(['listing_type' => $described->listingType, 'listing_slug' => $described->listingSlug]);
        }

        return $this->tool($request, $engine->layout($definition), $operation);
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
