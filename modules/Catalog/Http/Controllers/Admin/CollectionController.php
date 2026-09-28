<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Application\Collections\CollectionService;
use Modules\Catalog\Http\Requests\Admin\CollectionRequest;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Identity\Contracts\Data\ScopeRef;

final class CollectionController
{
    public function index(Brand $brand): Response
    {
        Gate::authorize('catalog.view', [ScopeRef::brand($brand->id)]);

        return Inertia::render('Catalog::Collections/Index', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'nav' => CatalogNavigation::for($brand),
            'collections' => ProductCollection::query()->with('translations')->withCount('styles')->orderBy('position')->orderBy('slug')->get()
                ->map(fn (ProductCollection $collection): array => [
                    'id' => $collection->id,
                    'slug' => $collection->slug,
                    'name' => $collection->translate('name'),
                    'status' => $collection->status,
                    'styles_count' => $collection->styles_count,
                ])->all(),
            'canManage' => Gate::allows('catalog.manage', [ScopeRef::brand($brand->id)]),
        ]);
    }

    public function create(Brand $brand): Response
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);

        return $this->form($brand, null);
    }

    public function store(Brand $brand, CollectionRequest $request, CollectionService $collections): RedirectResponse
    {
        $collection = $collections->save($brand->id, $request->toData(), $request->styleCodes());

        return redirect()->route('admin.catalog.collections.edit', ['collection' => $collection->id])->with('success', __('catalog::messages.saved'));
    }

    public function edit(Brand $brand, ProductCollection $collection): Response
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);

        return $this->form($brand, $collection->load(['translations', 'styles']));
    }

    public function update(Brand $brand, ProductCollection $collection, CollectionRequest $request, CollectionService $collections): RedirectResponse
    {
        $collections->save($brand->id, $request->toData(), $request->styleCodes(), $collection);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Brand $brand, ProductCollection $collection, CollectionService $collections): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        $collections->delete($collection);

        return redirect()->route('admin.catalog.collections.index')->with('success', __('catalog::messages.deleted'));
    }

    private function form(Brand $brand, ?ProductCollection $collection): Response
    {
        return Inertia::render('Catalog::Collections/Form', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'nav' => CatalogNavigation::for($brand),
            'collection' => $collection === null ? null : [
                'id' => $collection->id,
                'slug' => $collection->slug,
                'status' => $collection->status,
                'position' => $collection->position,
                'translations' => $collection->translationsByLocale(),
                'style_codes' => $collection->styles->pluck('style_code')->implode("\n"),
            ],
        ]);
    }
}
