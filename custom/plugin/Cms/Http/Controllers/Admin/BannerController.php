<?php

declare(strict_types=1);

namespace Plugin\Cms\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\CatalogLookup;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Shared\Support\StoreLocale;
use Plugin\Cms\Infrastructure\ExtraContent;
use Plugin\Cms\Persistence\CategoryBanner;

/**
 * Admin → Nội dung → Banner danh mục (store-content §3.3): mỗi danh mục một banner; ảnh desktop/mobile chọn từ Thư viện ảnh.
 * Trang riêng thay vì phần form ở form danh mục vì phần form của plugin chưa có kiểu trường chọn ảnh (backlog L6).
 */
final class BannerController
{
    public function __construct(
        private readonly ExtraContent $content,
        private readonly MediaDirectory $media,
        private readonly CatalogLookup $catalog,
    ) {}

    public function index(): Response
    {
        Gate::authorize('cms.view');
        $banners = CategoryBanner::query()->orderByDesc('id')->get();
        $categories = $this->categoryNames();
        $thumbs = $this->media->find(array_values(array_filter($banners->pluck('desktop_media_id')->all())));

        return Inertia::render('Cms::Banners/Index', [
            'banners' => $banners->map(fn (CategoryBanner $banner): array => [
                'id' => $banner->id, 'category' => $categories[$banner->category_id] ?? "#{$banner->category_id} (đã xoá)",
                'title' => $banner->text('title', StoreLocale::default(), StoreLocale::default()), 'active' => $banner->active,
                'thumb_url' => ($thumbs[$banner->desktop_media_id ?? 0] ?? null)?->urlFor(160),
            ])->all(),
            'urls' => ['index' => route('admin.plugins.vani-cms.banners.index'), 'create' => route('admin.plugins.vani-cms.banners.create'), 'pages' => route('admin.plugins.vani-cms.pages.index')],
            'can' => ['manage' => Gate::allows('cms.manage')],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('cms.manage');

        return $this->form(null);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('cms.manage');
        $data = $this->validated($request);
        $banner = $this->content->saveBanner(null, (int) $data['category_id'], $data);

        return redirect()->route('admin.plugins.vani-cms.banners.edit', $banner->id)->with('success', 'Đã lưu banner.');
    }

    public function edit(CategoryBanner $banner): Response
    {
        Gate::authorize('cms.view');

        return $this->form($banner);
    }

    public function update(Request $request, CategoryBanner $banner): RedirectResponse
    {
        Gate::authorize('cms.manage');
        $data = $this->validated($request);
        $this->content->saveBanner($banner, (int) $data['category_id'], $data);

        return back()->with('success', 'Đã lưu banner.');
    }

    public function destroy(CategoryBanner $banner): RedirectResponse
    {
        Gate::authorize('cms.manage');
        $this->content->deleteBanner($banner);

        return redirect()->route('admin.plugins.vani-cms.banners.index')->with('success', 'Đã xoá banner.');
    }

    private function form(?CategoryBanner $banner): Response
    {
        $ids = $banner === null ? [] : array_values(array_filter([$banner->desktop_media_id, $banner->mobile_media_id]));
        $media = $ids === [] ? [] : $this->media->find($ids);
        $image = fn (?int $id): ?array => $id === null ? null : ['id' => $id, 'url' => ($media[$id] ?? null)?->urlFor(400)];

        return Inertia::render('Cms::Banners/Form', [
            'banner' => $banner === null ? null : [
                'id' => $banner->id, 'category_id' => $banner->category_id, 'content' => (object) ($banner->content ?? []),
                'button_url' => $banner->button_url, 'active' => $banner->active,
                'desktop' => $image($banner->desktop_media_id), 'mobile' => $image($banner->mobile_media_id),
            ],
            'categories' => array_map(fn (array $category): array => ['value' => $category['id'], 'label' => $category['name'].($category['visible'] ? '' : ' (đang ẩn)')], $this->catalog->search('category', '', StoreLocale::default(), 200)),
            'urls' => [
                'index' => route('admin.plugins.vani-cms.banners.index'),
                'save' => $banner === null ? route('admin.plugins.vani-cms.banners.store') : route('admin.plugins.vani-cms.banners.update', $banner->id),
            ],
            'can' => ['manage' => Gate::allows('cms.manage')],
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function categoryNames(): array
    {
        $names = [];
        foreach ($this->catalog->search('category', '', StoreLocale::default(), 500) as $category) {
            $names[(int) $category['id']] = (string) $category['name'];
        }

        return $names;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $rules = [
            'category_id' => ['required', 'integer', 'min:1'],
            'button_url' => ['nullable', 'string', 'max:500'],
            'desktop_media_id' => ['nullable', 'integer'],
            'mobile_media_id' => ['nullable', 'integer'],
            'active' => ['boolean'],
        ];
        foreach (StoreLocale::supported() as $locale) {
            $rules["content.{$locale}.title"] = ['nullable', 'string', 'max:160'];
            $rules["content.{$locale}.markdown"] = ['nullable', 'string', 'max:1000'];
            $rules["content.{$locale}.button_text"] = ['nullable', 'string', 'max:60'];
        }

        $data = $request->validate($rules) + ['content' => []];
        if (! array_key_exists((int) $data['category_id'], $this->categoryNames())) {
            throw ValidationException::withMessages(['category_id' => 'Danh mục không tồn tại.']);
        }

        return $data;
    }
}
