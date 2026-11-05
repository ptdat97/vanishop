<?php

declare(strict_types=1);

namespace Plugin\Cms\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Plugin\Cms\Persistence\Category;
use Plugin\Cms\Persistence\Post;

/**
 * Admin → Nội dung → Chuyên mục (1.4.0, reference-parity §3.17-1): một cấp; slug không trùng bài viết (cùng /tin-tuc/…).
 * Xoá chuyên mục: bài viết của nó thành "không chuyên mục".
 */
final class CategoryController
{
    public function index(): Response
    {
        Gate::authorize('cms.view');

        return Inertia::render('Cms::Categories/Index', [
            'categories' => Category::query()->withCount('posts')->orderBy('sort_order')->orderBy('name')->get()->map(fn (Category $category): array => [
                'id' => $category->id, 'slug' => $category->slug, 'name' => $category->name, 'description' => $category->description,
                'meta_title' => $category->meta_title, 'meta_description' => $category->meta_description,
                'sort_order' => $category->sort_order, 'posts_count' => $category->posts_count,
            ])->all(),
            'urls' => [
                'index' => route('admin.plugins.vani-cms.categories.index'),
                'pages' => route('admin.plugins.vani-cms.pages.index'), 'posts' => route('admin.plugins.vani-cms.posts.index'),
            ],
            'can' => ['manage' => Gate::allows('cms.manage')],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('cms.manage');
        if (Category::query()->count() >= Category::MAX) {
            throw ValidationException::withMessages(['name' => 'Tối đa '.Category::MAX.' chuyên mục.']);
        }
        Category::query()->create($this->validated($request, null));

        return back()->with('success', 'Đã lưu chuyên mục.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        Gate::authorize('cms.manage');
        $category->update($this->validated($request, $category));

        return back()->with('success', 'Đã lưu chuyên mục.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('cms.manage');
        DB::transaction(function () use ($category): void {
            Post::query()->where('category_id', $category->id)->update(['category_id' => null]);
            $category->delete();
        });

        return back()->with('success', 'Đã xoá chuyên mục.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Category $category): array
    {
        $request->merge(['slug' => Str::slug((string) ($request->input('slug') ?: $request->input('name')))]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('plg_cms_categories', 'slug')->ignore($category?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
        ]);
        if (Post::query()->where('slug', $data['slug'])->exists()) {
            throw ValidationException::withMessages(['slug' => 'Đường dẫn đã là của một bài viết.']);
        }

        return [...$data, 'sort_order' => (int) ($data['sort_order'] ?? 0)];
    }
}
