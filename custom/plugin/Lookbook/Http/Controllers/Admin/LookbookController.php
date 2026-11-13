<?php

declare(strict_types=1);

namespace Plugin\Lookbook\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\CatalogLookup;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Shared\Support\StoreLocale;
use Plugin\Lookbook\Infrastructure\Lookbooks;
use Plugin\Lookbook\Persistence\Lookbook;

/**
 * Admin → Nội dung → Lookbook (đặc tả §3.2): danh sách, tạo/sửa/xoá; ảnh chọn từ Thư viện ảnh.
 */
final class LookbookController
{
    public function __construct(
        private readonly Lookbooks $lookbooks,
        private readonly MediaDirectory $media,
        private readonly CatalogLookup $catalog,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('lookbook.view');
        $validated = $request->validate([
            'per_page' => ['sometimes', 'integer', Rule::in([20, 50, 100])],
        ]);
        $perPage = (int) ($validated['per_page'] ?? 20);
        $search = trim((string) $request->query('q', ''));
        $paginator = Lookbook::query()->withCount('images')
            ->when($search !== '', fn ($query) => $query->where(fn ($inner) => $inner->where('slug', 'like', '%'.addcslashes($search, '%_\\').'%')->orWhere('translations', 'like', '%'.addcslashes($search, '%_\\').'%')))
            ->orderBy('position')->orderByDesc('id')->paginate($perPage)->withQueryString();
        $covers = $this->media->find(array_values(array_filter($paginator->getCollection()->pluck('cover_media_id')->all())));

        return Inertia::render('Lookbook::Index', [
            'search' => $search,
            'lookbooks' => $paginator->through(fn (Lookbook $lookbook): array => [
                'id' => $lookbook->id, 'name' => $lookbook->text('name', StoreLocale::default()), 'slug' => $lookbook->slug,
                'images_count' => $lookbook->images_count, 'active' => $lookbook->active, 'position' => $lookbook->position,
                'cover_url' => ($covers[$lookbook->cover_media_id ?? 0] ?? null)?->urlFor(160),
            ]),
            'urls' => ['index' => route('admin.plugins.vani-lookbook.index'), 'create' => route('admin.plugins.vani-lookbook.create')],
            'can' => ['manage' => Gate::allows('lookbook.manage')],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('lookbook.manage');

        return $this->form(null);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('lookbook.manage');
        $lookbook = $this->lookbooks->save(null, $this->validated($request));

        return redirect()->route('admin.plugins.vani-lookbook.edit', $lookbook->id)->with('success', 'Đã lưu lookbook.');
    }

    public function edit(Lookbook $lookbook): Response
    {
        Gate::authorize('lookbook.view');

        return $this->form($lookbook->load('images'));
    }

    public function update(Request $request, Lookbook $lookbook): RedirectResponse
    {
        Gate::authorize('lookbook.manage');
        $this->lookbooks->save($lookbook, $this->validated($request, $lookbook));

        return back()->with('success', 'Đã lưu lookbook.');
    }

    public function destroy(Lookbook $lookbook): RedirectResponse
    {
        Gate::authorize('lookbook.manage');
        $this->lookbooks->delete($lookbook);

        return redirect()->route('admin.plugins.vani-lookbook.index')->with('success', 'Đã xoá lookbook.');
    }

    private function form(?Lookbook $lookbook): Response
    {
        $ids = $lookbook === null ? [] : [...$lookbook->images->pluck('media_id')->all(), ...($lookbook->cover_media_id === null ? [] : [$lookbook->cover_media_id])];
        $media = $ids === [] ? [] : $this->media->find(array_values(array_unique($ids)));

        return Inertia::render('Lookbook::Form', [
            'lookbook' => $lookbook === null ? null : [
                'id' => $lookbook->id, 'slug' => $lookbook->slug, 'translations' => $lookbook->translations,
                'cover' => $lookbook->cover_media_id === null ? null : ['id' => $lookbook->cover_media_id, 'url' => ($media[$lookbook->cover_media_id] ?? null)?->urlFor(400)],
                'images' => $lookbook->images->map(fn ($image): array => ['media_id' => $image->media_id, 'alt' => (object) ($image->alt ?? []), 'thumb_url' => ($media[$image->media_id] ?? null)?->urlFor(160)])->all(),
                'category_id' => $lookbook->category_id, 'position' => $lookbook->position, 'active' => $lookbook->active, 'lock_version' => $lookbook->lock_version,
                'public_url' => $lookbook->active ? vani_route('storefront.p.vani-lookbook.show', $lookbook->slug) : null,
            ],
            'categories' => array_map(fn (array $category): array => ['value' => $category['id'], 'label' => $category['name'].($category['visible'] ? '' : ' (đang ẩn)')], $this->catalog->search('category', '', StoreLocale::default(), 200)),
            'urls' => [
                'index' => route('admin.plugins.vani-lookbook.index'),
                'save' => $lookbook === null ? route('admin.plugins.vani-lookbook.store') : route('admin.plugins.vani-lookbook.update', $lookbook->id),
            ],
            'can' => ['manage' => Gate::allows('lookbook.manage')],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Lookbook $lookbook = null): array
    {
        $rules = [
            // Khoá lạc quan khi sửa: người lưu sau được báo, không ghi đè im lặng.
            'lock_version' => [$lookbook === null ? 'nullable' : 'required', 'integer', 'min:0'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'cover_media_id' => ['nullable', 'integer'],
            'category_id' => ['nullable', 'integer'],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'active' => ['boolean'],
            'images' => ['nullable', 'array', 'max:'.Lookbooks::MAX_IMAGES],
            'images.*.media_id' => ['required', 'integer', 'min:1'],
            'images.*.alt' => ['nullable', 'array'],
            'images.*.alt.*' => ['nullable', 'string', 'max:200'],
        ];
        foreach (StoreLocale::supported() as $locale) {
            $rules["translations.{$locale}.name"] = ['nullable', 'string', 'max:160'];
            $rules["translations.{$locale}.description"] = ['nullable', 'string', 'max:5000'];
            $rules["translations.{$locale}.meta_title"] = ['nullable', 'string', 'max:200'];
            $rules["translations.{$locale}.meta_description"] = ['nullable', 'string', 'max:300'];
        }

        return $request->validate($rules) + ['translations' => []];
    }
}
