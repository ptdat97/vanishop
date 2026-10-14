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
use Modules\Extension\Contracts\AdminScreen;

/**
 * Thương hiệu — thuộc tính catalog (ADR-028).
 */
final class BrandController
{
    use ConvertsDomainErrors;

    public function index(AdminScreen $screen): Response
    {
        Gate::authorize('catalog.view');
        $brands = Brand::query()->withCount('styles')->orderBy('position')->orderBy('name')->get();

        return Inertia::render('Catalog::Taxonomy/Brands', [
            'nav' => CatalogNavigation::all(),
            'brands' => $brands->map(fn (Brand $brand): array => [
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
            // Phần form của plugin: khoá `new` cho form thêm, id thương hiệu cho form sửa (sửa ngay trên trang danh sách).
            // `+` giữ khoá là id thương hiệu (spread `...` đánh số lại khoá số).
            'extensionSections' => Gate::allows('catalog.manage')
                ? ['new' => $screen->formSections('brand', null)] + $brands->mapWithKeys(fn (Brand $brand): array => [$brand->id => $screen->formSections('brand', $brand->id)])->all()
                : [],
        ]);
    }

    public function store(BrandRequest $request, TaxonomyService $taxonomy, AdminScreen $screen): RedirectResponse
    {
        $request->validate($screen->validationRules('brand'));
        $this->orFormError(fn () => $screen->saving('brand', (array) $request->input('extensions', []), fn () => $taxonomy->saveBrand($request->toData()), fn (Brand $brand): int => $brand->id));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function update(Brand $brand, BrandRequest $request, TaxonomyService $taxonomy, AdminScreen $screen): RedirectResponse
    {
        $request->validate($screen->validationRules('brand'));
        $this->orFormError(fn () => $screen->saving('brand', (array) $request->input('extensions', []), fn () => $taxonomy->saveBrand($request->toData(), $brand), fn (): int => $brand->id));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Brand $brand, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $this->orFormError(fn () => $taxonomy->deleteBrand($brand));

        return back()->with('success', __('catalog::messages.deleted'));
    }
}
