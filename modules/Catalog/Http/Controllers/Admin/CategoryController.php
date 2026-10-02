<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Categories\CategoryService;
use Modules\Catalog\Application\Categories\CategoryTreeQuery;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Http\Requests\Admin\CategoryImageRequest;
use Modules\Catalog\Http\Requests\Admin\CategoryRequest;
use Modules\Catalog\Persistence\Models\Category;

final class CategoryController
{
    use ConvertsDomainErrors;

    public function index(CategoryTreeQuery $tree): Response
    {
        Gate::authorize('catalog.view');

        return Inertia::render('Catalog::Categories/Index', [
            'nav' => CatalogNavigation::all(),
            'tree' => $tree->tree(),
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function create(CategoryTreeQuery $tree): Response
    {
        Gate::authorize('catalog.manage');

        return Inertia::render('Catalog::Categories/Form', [
            'nav' => CatalogNavigation::all(),
            'category' => null,
            'parents' => $this->parentOptions($tree->tree()),
        ]);
    }

    public function store(CategoryRequest $request, CategoryService $categories): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $category = $this->orFormError(fn () => $categories->create($request->toInput()));

        return redirect()->route('admin.catalog.categories.edit', ['category' => $category->id])->with('success', __('catalog::messages.saved'));
    }

    public function edit(Category $category, CategoryTreeQuery $tree): Response
    {
        Gate::authorize('catalog.manage');

        $category->load(['translations', 'media.media']);
        $image = $category->media->firstWhere('role', 'image');

        return Inertia::render('Catalog::Categories/Form', [
            'nav' => CatalogNavigation::all(),
            'category' => [
                'id' => $category->id,
                'slug' => $category->slug,
                'parent_id' => $category->parent_id,
                'status' => $category->status->value,
                'position' => $category->position,
                'lock_version' => $category->lock_version,
                'translations' => $category->translationsByLocale(),
                'image_url' => $image?->media->url(),
            ],
            'parents' => $this->parentOptions($tree->tree(), exclude: $category->id),
        ]);
    }

    public function update(Category $category, CategoryRequest $request, CategoryService $categories): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $this->orFormError(fn () => $categories->update($category, $request->toInput(), (int) $request->validated('lock_version')));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Category $category, CategoryService $categories): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $this->orFormError(fn () => $categories->delete($category));

        return redirect()->route('admin.catalog.categories.index')->with('success', __('catalog::messages.deleted'));
    }

    public function uploadImage(Category $category, CategoryImageRequest $request, MediaLibrary $media): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $stored = $media->store($request->file('image'));
        $media->attachSingle($category, 'image', $stored, $request->validated('alt'));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function removeImage(Category $category, MediaLibrary $media): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $media->detach($category, 'image');

        return back()->with('success', __('catalog::messages.saved'));
    }

    /**
     * Danh sách phẳng để chọn danh mục cha (bỏ chính nó và cây con khi sửa).
     *
     * @param  list<array<string, mixed>>  $nodes
     * @return list<array{id: int, label: string}>
     */
    private function parentOptions(array $nodes, ?int $exclude = null): array
    {
        $options = [];
        foreach ($nodes as $node) {
            if ($node['id'] === $exclude) {
                continue;
            }
            $options[] = ['id' => $node['id'], 'label' => str_repeat('— ', $node['depth'] - 1).$node['name']];
            $options = [...$options, ...$this->parentOptions($node['children'], $exclude)];
        }

        return $options;
    }
}
