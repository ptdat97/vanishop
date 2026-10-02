<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Domain\SizeSystem;
use Modules\Catalog\Http\Requests\Admin\SizeRequest;
use Modules\Catalog\Persistence\Models\Size;

final class SizeController
{
    public function index(): Response
    {
        Gate::authorize('catalog.view');

        return Inertia::render('Catalog::Taxonomy/Sizes', [
            'nav' => CatalogNavigation::all(),
            'sizes' => Size::query()->orderBy('size_system')->orderBy('sort_order')->orderBy('code')->get()
                ->map(fn (Size $size): array => [
                    'id' => $size->id,
                    'size_system' => $size->size_system->value,
                    'code' => $size->code,
                    'sort_order' => $size->sort_order,
                ])->all(),
            'systems' => array_column(SizeSystem::cases(), 'value'),
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function store(SizeRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $taxonomy->saveSize($request->toData());

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function update(Size $size, SizeRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $taxonomy->saveSize($request->toData(), $size);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Size $size, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $taxonomy->deleteSize($size);

        return back()->with('success', __('catalog::messages.deleted'));
    }
}
