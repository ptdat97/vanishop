<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Domain\ColorFamily;
use Modules\Catalog\Http\Requests\Admin\ColorRequest;
use Modules\Catalog\Persistence\Models\Color;

final class ColorController
{
    public function index(): Response
    {
        Gate::authorize('catalog.view');

        return Inertia::render('Catalog::Taxonomy/Colors', [
            'nav' => CatalogNavigation::all(),
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
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function store(ColorRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $taxonomy->saveColor($request->toData());

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function update(Color $color, ColorRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $taxonomy->saveColor($request->toData(), $color);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Color $color, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $taxonomy->deleteColor($color);

        return back()->with('success', __('catalog::messages.deleted'));
    }
}
