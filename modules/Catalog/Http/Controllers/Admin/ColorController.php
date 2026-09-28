<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Domain\ColorFamily;
use Modules\Catalog\Http\Requests\Admin\ColorRequest;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Identity\Contracts\Data\ScopeRef;

final class ColorController
{
    public function index(Brand $brand): Response
    {
        Gate::authorize('catalog.view', [ScopeRef::brand($brand->id)]);

        return Inertia::render('Catalog::Taxonomy/Colors', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
            'nav' => CatalogNavigation::for($brand),
            'colors' => Color::query()->with('translations')->orderBy('position')->orderBy('code')->get()
                ->map(fn (Color $color): array => [
                    'id' => $color->id,
                    'code' => $color->code,
                    'color_family' => $color->color_family->value,
                    'hex' => $color->hex,
                    'position' => $color->position,
                    'translations' => $color->translationsByLocale(),
                ])->all(),
            'families' => array_column(ColorFamily::cases(), 'value'),
            'canManage' => Gate::allows('catalog.manage', [ScopeRef::brand($brand->id)]),
        ]);
    }

    public function store(Brand $brand, ColorRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        $taxonomy->saveColor($brand->id, $request->toData());

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function update(Brand $brand, Color $color, ColorRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        $taxonomy->saveColor($brand->id, $request->toData(), $color);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Brand $brand, Color $color, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage', [ScopeRef::brand($brand->id)]);
        $taxonomy->deleteColor($color);

        return back()->with('success', __('catalog::messages.deleted'));
    }
}
