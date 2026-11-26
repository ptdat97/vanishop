<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\AdminQueries;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Http\Requests\Admin\BrandRequest;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Extension\Contracts\AdminScreen;

/**
 * Thương hiệu — thuộc tính catalog (ADR-028). Cùng khuôn Admin với danh mục và bộ sưu tập: danh sách + trang sửa riêng.
 */
final class BrandController
{
    use ConvertsDomainErrors;

    public function __construct(private readonly AdminQueries $queries) {}

    public function index(): Response
    {
        Gate::authorize('catalog.view');
        $brands = $this->queries->brandsWithUsage();
        $logos = $this->queries->mediaByIds($brands->pluck('logo_media_id')->filter()->map(fn (mixed $id): int => (int) $id)->values()->all());

        return Inertia::render('Catalog::Brands/Index', [
            'nav' => CatalogNavigation::all(),
            'labels' => __('catalog::labels'),
            'brands' => $brands->map(fn (Brand $brand): array => [
                'id' => $brand->id,
                'code' => $brand->code,
                'slug' => $brand->slug,
                'name' => $brand->name,
                'status' => $brand->status,
                'image_url' => $brand->logo_media_id === null ? null : $logos->get($brand->logo_media_id)?->url(200),
                'products_count' => (int) $brand->getAttribute('products_count'),
            ])->all(),
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function create(AdminScreen $screen): Response
    {
        Gate::authorize('catalog.manage');

        return $this->form(null, $screen);
    }

    public function store(BrandRequest $request, TaxonomyService $taxonomy, AdminScreen $screen): RedirectResponse
    {
        $request->validate($screen->validationRules('brand'));
        $brand = $this->orFormError(fn () => $screen->saving('brand', (array) $request->input('extensions', []), fn () => $taxonomy->saveBrand($request->toData()), fn (Brand $brand): int => $brand->id));

        return redirect()->route('admin.catalog.brands.edit', ['brand' => $brand->id])->with('success', __('catalog::messages.saved'));
    }

    public function edit(Brand $brand, AdminScreen $screen): Response
    {
        Gate::authorize('catalog.manage');

        return $this->form($brand, $screen);
    }

    public function update(Brand $brand, BrandRequest $request, TaxonomyService $taxonomy, AdminScreen $screen): RedirectResponse
    {
        $request->validate($screen->validationRules('brand'));
        $this->orFormError(fn () => $screen->saving('brand', (array) $request->input('extensions', []), fn () => $taxonomy->saveBrand($request->toData(), $brand, (int) $request->validated('lock_version')), fn (): int => $brand->id));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Brand $brand, TaxonomyService $taxonomy): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $this->orFormError(fn () => $taxonomy->deleteBrand($brand));

        return redirect()->route('admin.catalog.brands.index')->with('success', __('catalog::messages.deleted'));
    }

    private function form(?Brand $brand, AdminScreen $screen): Response
    {
        return Inertia::render('Catalog::Brands/Form', [
            'nav' => CatalogNavigation::all(),
            'labels' => __('catalog::labels'),
            'brand' => $brand === null ? null : [
                'id' => $brand->id,
                'code' => $brand->code,
                'slug' => $brand->slug,
                'name' => $brand->name,
                'description' => $brand->description,
                'meta_title' => $brand->meta_title,
                'meta_description' => $brand->meta_description,
                'logo_media_id' => $brand->logo_media_id,
                'status' => $brand->status,
                'position' => $brand->position,
                'lock_version' => $brand->lock_version,
                'products_count' => $brand->products()->count(),
            ],
            'extensionSections' => $screen->formSections('brand', $brand?->id),
            'extensionLinks' => $brand === null ? [] : $screen->links('brand', $brand->id),
        ]);
    }
}
