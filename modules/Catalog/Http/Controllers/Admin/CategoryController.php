<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Categories\CategoryService;
use Modules\Catalog\Application\Categories\CategoryTreeQuery;
use Modules\Catalog\Http\Requests\Admin\CategoryRequest;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Mediable;
use Modules\Extension\Contracts\AdminScreen;

final class CategoryController
{
    use ConvertsDomainErrors;

    public function index(CategoryTreeQuery $tree): Response
    {
        Gate::authorize('catalog.view');

        return Inertia::render('Catalog::Categories/Index', [
            'nav' => CatalogNavigation::all(),
            'labels' => __('catalog::labels'),
            'tree' => $tree->tree(),
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function create(CategoryTreeQuery $tree): Response
    {
        Gate::authorize('catalog.manage');

        return Inertia::render('Catalog::Categories/Form', [
            'nav' => CatalogNavigation::all(),
            'labels' => __('catalog::labels'),
            'category' => null,
            'parents' => $this->parentOptions($tree->tree()),
            'extensionSections' => app(AdminScreen::class)->formSections('category', null),
            'extensionLinks' => [],
        ]);
    }

    public function store(CategoryRequest $request, CategoryService $categories, AdminScreen $screen): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $request->validate($screen->validationRules('category'));

        $category = $this->orFormError(fn () => $screen->saving('category', (array) $request->input('extensions', []), fn () => $categories->create($request->toInput()), fn ($category): int => $category->id));

        return redirect()->route('admin.catalog.categories.edit', ['category' => $category->id])->with('success', __('catalog::messages.saved'));
    }

    public function edit(Category $category, CategoryTreeQuery $tree): Response
    {
        Gate::authorize('catalog.manage');

        $category->load(['translations', 'media']);
        /** @var Mediable|null $image */
        $image = $category->media->firstWhere('role', 'image');
        $screen = app(AdminScreen::class);

        return Inertia::render('Catalog::Categories/Form', [
            'nav' => CatalogNavigation::all(),
            'labels' => __('catalog::labels'),
            'category' => [
                'id' => $category->id,
                'slug' => $category->slug,
                'parent_id' => $category->parent_id,
                'status' => $category->status->value,
                'position' => $category->position,
                'lock_version' => $category->lock_version,
                'translations' => $category->translationsByLocale(),
                'image_media_id' => $image?->media_id,
            ],
            'parents' => $this->parentOptions($tree->tree(), exclude: $category->id),
            'extensionSections' => $screen->formSections('category', $category->id),
            'extensionLinks' => $screen->links('category', $category->id),
        ]);
    }

    public function update(Category $category, CategoryRequest $request, CategoryService $categories, AdminScreen $screen): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $request->validate($screen->validationRules('category'));

        $this->orFormError(fn () => $screen->saving('category', (array) $request->input('extensions', []), fn () => $categories->update($category, $request->toInput(), (int) $request->validated('lock_version')), fn (): int => $category->id));

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(Category $category, CategoryService $categories): RedirectResponse
    {
        Gate::authorize('catalog.manage');

        $this->orFormError(fn () => $categories->delete($category));

        return redirect()->route('admin.catalog.categories.index')->with('success', __('catalog::messages.deleted'));
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
