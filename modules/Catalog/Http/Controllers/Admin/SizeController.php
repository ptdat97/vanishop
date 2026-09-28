<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Domain\SizeSystem;
use Modules\Catalog\Http\Requests\Admin\SizeRequest;
use Modules\Catalog\Persistence\Models\Size;
use Modules\Identity\Contracts\Data\ScopeRef;

final class SizeController
{
    public function index(Brand $brand): Response
    {
        Gate::authorize('catalog.view', [ScopeRef::brand($brand->id)]);

        return Inertia::render('Catalog::Taxonomy/Sizes', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'nav' => CatalogNavigation::for($brand),
            'sizes' => Size::query()->orderBy('size_system')->orderBy('sort_order')->orderBy('code')->get()
                ->map(fn (Size $size): array => [
                    'id' => $size->id,
                    'size_system' => $size->size_system->value,
                    'code' => $size->code,
                    'sort_order' => $size->sort_order,
                ])->all(),
            'systems' => array_column(SizeSystem::cases(), 'value'),
            'canManage' => Gate::allows('catalog.manage', [ScopeRef::brand($brand->id)]),
        ]);
    }

    public function store(Brand $brand, SizeRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        $taxonomy->saveSize($brand->id, $request->toData());

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function update(Brand $brand, Size $size, SizeRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        $taxonomy->saveSize($brand->id, $request->toData(), $size);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Brand $brand, Size $size, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        $taxonomy->deleteSize($size);

        return back()->with('success', __('catalog::messages.deleted'));
    }
}
