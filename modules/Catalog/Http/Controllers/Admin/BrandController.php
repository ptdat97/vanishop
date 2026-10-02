<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Http\Requests\Admin\BrandRequest;
use Modules\Catalog\Persistence\Models\Brand;

/**
 * Thương hiệu — thuộc tính catalog (ADR-028).
 */
final class BrandController
{
    use ConvertsDomainErrors;

    public function index(): Response
    {
        Gate::authorize('catalog.view');

        return Inertia::render('Catalog::Taxonomy/Brands', [
            'nav' => CatalogNavigation::all(),
            'brands' => Brand::query()->withCount('styles')->orderBy('position')->orderBy('name')->get()
                ->map(fn (Brand $brand): array => [
                    'id' => $brand->id,
                    'code' => $brand->code,
                    'slug' => $brand->slug,
                    'name' => $brand->name,
                    'description' => $brand->description,
                    'status' => $brand->status,
                    'position' => $brand->position,
                    'styles_count' => (int) $brand->getAttribute('styles_count'),
                ])->all(),
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function store(BrandRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        $taxonomy->saveBrand($request->toData());

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function update(Brand $brand, BrandRequest $request, TaxonomyService $taxonomy): RedirectResponse
    {
        $taxonomy->saveBrand($request->toData(), $brand);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Brand $brand, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $this->orFormError(fn () => $taxonomy->deleteBrand($brand));

        return back()->with('success', __('catalog::messages.deleted'));
    }
}
