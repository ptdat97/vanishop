<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\AdminQueries;
use Modules\Catalog\Application\Collections\CollectionService;
use Modules\Catalog\Http\Requests\Admin\CollectionRequest;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Extension\Contracts\AdminScreen;

/**
 * Bộ sưu tập thủ công. Cùng khuôn Admin với thương hiệu và danh mục: danh sách + trang sửa riêng.
 */
final class CollectionController
{
    use ConvertsDomainErrors;

    public function __construct(private readonly AdminQueries $queries) {}

    public function index(): Response
    {
        Gate::authorize('catalog.view');
        $collections = $this->queries->collections();
        $images = $this->queries->mediaByIds($collections->pluck('image_media_id')->filter()->map(fn (mixed $id): int => (int) $id)->values()->all());

        return Inertia::render('Catalog::Collections/Index', [
            'nav' => CatalogNavigation::all(),
            'labels' => __('catalog::labels'),
            'collections' => $collections->map(fn (ProductCollection $collection): array => [
                'id' => $collection->id,
                'slug' => $collection->slug,
                'name' => $collection->translate('name'),
                'status' => $collection->status,
                'image_url' => $collection->image_media_id === null ? null : $images->get($collection->image_media_id)?->url(200),
                'products_count' => (int) $collection->getAttribute('products_count'),
            ])->all(),
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function create(AdminScreen $screen): Response
    {
        Gate::authorize('catalog.manage');

        return $this->form(null, $screen);
    }

    public function store(CollectionRequest $request, CollectionService $collections, AdminScreen $screen): RedirectResponse
    {
        $request->validate($screen->validationRules('collection'));
        $collection = $this->orFormError(fn () => $screen->saving('collection', (array) $request->input('extensions', []),
            fn () => $collections->save($request->toData(), $request->productCodes()), fn (ProductCollection $collection): int => $collection->id));

        return redirect()->route('admin.catalog.collections.edit', ['collection' => $collection->id])->with('success', __('catalog::messages.saved'));
    }

    public function edit(ProductCollection $collection, AdminScreen $screen): Response
    {
        Gate::authorize('catalog.manage');

        return $this->form($collection->load(['translations', 'products']), $screen);
    }

    public function update(ProductCollection $collection, CollectionRequest $request, CollectionService $collections, AdminScreen $screen): RedirectResponse
    {
        $request->validate($screen->validationRules('collection'));
        $this->orFormError(fn () => $screen->saving('collection', (array) $request->input('extensions', []),
            fn () => $collections->save($request->toData(), $request->productCodes(), $collection, (int) $request->validated('lock_version')), fn (): int => $collection->id));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(ProductCollection $collection, CollectionService $collections): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $collections->delete($collection);

        return redirect()->route('admin.catalog.collections.index')->with('success', __('catalog::messages.deleted'));
    }

    private function form(?ProductCollection $collection, AdminScreen $screen): Response
    {
        return Inertia::render('Catalog::Collections/Form', [
            'nav' => CatalogNavigation::all(),
            'labels' => __('catalog::labels'),
            'collection' => $collection === null ? null : [
                'id' => $collection->id,
                'slug' => $collection->slug,
                'status' => $collection->status,
                'position' => $collection->position,
                'image_media_id' => $collection->image_media_id,
                'lock_version' => $collection->lock_version,
                'translations' => $collection->translationsByLocale(),
                'product_codes' => $collection->products->pluck('product_code')->implode("\n"),
                'products_count' => $collection->products->count(),
            ],
            'extensionSections' => $screen->formSections('collection', $collection?->id),
            'extensionLinks' => $collection === null ? [] : $screen->links('collection', $collection->id),
        ]);
    }
}
