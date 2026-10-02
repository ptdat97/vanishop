<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Collections\CollectionService;
use Modules\Catalog\Http\Requests\Admin\CollectionRequest;
use Modules\Catalog\Persistence\Models\ProductCollection;

final class CollectionController
{
    public function index(): Response
    {
        Gate::authorize('catalog.view');

        return Inertia::render('Catalog::Collections/Index', [
            'nav' => CatalogNavigation::all(),
            'collections' => ProductCollection::query()->with('translations')->withCount('styles')->orderBy('position')->orderBy('slug')->get()
                ->map(fn (ProductCollection $collection): array => [
                    'id' => $collection->id,
                    'slug' => $collection->slug,
                    'name' => $collection->translate('name'),
                    'status' => $collection->status,
                    'styles_count' => $collection->styles_count,
                ])->all(),
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('catalog.manage');

        return $this->form(null);
    }

    public function store(CollectionRequest $request, CollectionService $collections): RedirectResponse
    {
        $collection = $collections->save($request->toData(), $request->styleCodes());

        return redirect()->route('admin.catalog.collections.edit', ['collection' => $collection->id])->with('success', __('catalog::messages.saved'));
    }

    public function edit(ProductCollection $collection): Response
    {
        Gate::authorize('catalog.manage');

        return $this->form($collection->load(['translations', 'styles']));
    }

    public function update(ProductCollection $collection, CollectionRequest $request, CollectionService $collections): RedirectResponse
    {
        $collections->save($request->toData(), $request->styleCodes(), $collection);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(ProductCollection $collection, CollectionService $collections): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $collections->delete($collection);

        return redirect()->route('admin.catalog.collections.index')->with('success', __('catalog::messages.deleted'));
    }

    private function form(?ProductCollection $collection): Response
    {
        return Inertia::render('Catalog::Collections/Form', [
            'nav' => CatalogNavigation::all(),
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
