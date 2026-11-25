<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Modules\Storefront\Application\Builder\BuilderDrafts;

/** Xem trước bản nháp và nhập JSON trong Builder trang chủ. */
final class BuilderDraftController
{
    public function __invoke(Request $request, string $operation, BuilderDrafts $drafts): JsonResponse
    {
        Gate::authorize('storefront.manage');

        return match ($operation) {
            'preview' => $drafts->preview($request),
            'import' => $drafts->import($request),
            default => abort(404),
        };
    }
}
