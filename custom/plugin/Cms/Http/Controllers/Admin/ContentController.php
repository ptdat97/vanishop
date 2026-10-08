<?php

declare(strict_types=1);

namespace Plugin\Cms\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\MediaDirectory;
use Plugin\Cms\Infrastructure\CmsMedia;
use Plugin\Cms\Infrastructure\Markdown;
use Plugin\Cms\Infrastructure\Navigation;
use Plugin\Cms\Persistence\Content;
use Plugin\Cms\Persistence\Page;
use Plugin\Cms\Persistence\Post;

/**
 * Admin → Nội dung: trang (`pages`) và bài viết (`posts`) dùng chung màn hình, phân biệt bằng `kind` của route.
 */
final class ContentController
{
    // Tham số route truyền theo vị trí: tham số URI (`id`) đứng trước giá trị mặc định (`kind`).

    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function __construct(
        private readonly CmsMedia $covers,
        private readonly MediaDirectory $media,
        private readonly Markdown $markdown,
    ) {}

    public function index(Request $request, string $kind): Response
    {
        Gate::authorize('cms.view');
        $search = trim((string) $request->query('q', ''));
        $items = $this->model($kind)->newQuery()
            ->when($search !== '', fn ($query) => $query->where('title', 'like', '%'.$search.'%'))
            ->orderByDesc($kind === 'posts' ? 'published_at' : 'updated_at')->orderByDesc('id')
            ->paginate(20)->withQueryString();

        return Inertia::render('Cms::Content/Index', [
            'kind' => $kind,
            'search' => $search,
            'items' => $items->through(fn (Content $item): array => [
                'id' => $item->id, 'title' => $item->title, 'slug' => $item->slug, 'status' => $this->status($item),
                'published_at' => $item->published_at?->timezone(self::TIMEZONE)->format('d/m/Y H:i'),
                'updated_at' => $item->updated_at?->timezone(self::TIMEZONE)->format('d/m/Y H:i'),
                'url' => $item->isPublic() ? $this->publicUrl($kind, $item->slug) : null,
            ]),
            'can' => ['manage' => Gate::allows('cms.manage')],
            'urls' => $this->urls($kind),
        ]);
    }

    public function create(string $kind): Response
    {
        Gate::authorize('cms.manage');

        return $this->form($kind, null);
    }

    public function store(Request $request, string $kind): RedirectResponse
    {
        Gate::authorize('cms.manage');
        $item = $this->model($kind)->newInstance();
        $this->fill($item, $request, $kind);

        return redirect()->route("admin.plugins.vani-cms.{$kind}.edit", $item->id)->with('success', 'Đã lưu.');
    }

    public function edit(int $id, string $kind): Response
    {
        Gate::authorize('cms.view');

        return $this->form($kind, $this->find($kind, $id));
    }

    public function update(Request $request, int $id, string $kind): RedirectResponse
    {
        Gate::authorize('cms.manage');
        $this->fill($this->find($kind, $id), $request, $kind);

        return back()->with('success', 'Đã lưu.');
    }

    public function destroy(int $id, string $kind): RedirectResponse
    {
        Gate::authorize('cms.manage');
        $item = $this->find($kind, $id);
        DB::transaction(function () use ($item, $kind): void {
            $item->delete();
            $this->media->releaseUsages($this->ownerType($kind), $item->id);
        });
        Navigation::forget();

        return redirect()->route("admin.plugins.vani-cms.{$kind}.index")->with('success', 'Đã xoá.');
    }

    /** Xem trước Markdown đã render (cùng bộ render với storefront). */
    public function preview(Request $request): JsonResponse
    {
        Gate::authorize('cms.view');
        $data = $request->validate(['body' => ['nullable', 'string', 'max:200000']]);

        return response()->json(['html' => $this->markdown->html((string) ($data['body'] ?? ''))]);
    }

    private function form(string $kind, ?Content $item): Response
    {
        return Inertia::render('Cms::Content/Form', [
            'kind' => $kind,
            'item' => $item === null ? null : [
                'id' => $item->id, 'title' => $item->title, 'slug' => $item->slug, 'body' => $item->body,
                'meta_title' => $item->meta_title, 'meta_description' => $item->meta_description, 'status' => $item->status,
                'published_at' => $item->published_at?->timezone(self::TIMEZONE)->format('Y-m-d\TH:i'),
                'excerpt' => $item instanceof Post ? $item->excerpt : null,
                'cover_media_id' => $item instanceof Post ? $item->cover_media_id : null,
                'cover_url' => $item instanceof Post ? $this->covers->coverUrl($item, 800) : null,
                'show_in_header' => $item instanceof Page && $item->show_in_header,
                'show_in_footer' => $item instanceof Page && $item->show_in_footer,
                'sort_order' => $item instanceof Page ? $item->sort_order : 0,
                'public_url' => $item->isPublic() ? $this->publicUrl($kind, $item->slug) : null,
                // Xem trước bản nháp trên storefront: link ký, hết hạn sau 30 phút.
                'preview_url' => URL::temporarySignedRoute($kind === 'pages' ? 'storefront.p.vani-cms.page' : 'storefront.p.vani-cms.post', now()->addMinutes(30), ['slug' => $item->slug]),
            ],
            'can' => ['manage' => Gate::allows('cms.manage')],
            'urls' => [...$this->urls($kind), 'item' => $item === null ? null : route("admin.plugins.vani-cms.{$kind}.update", $item->id)],
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function urls(string $kind): array
    {
        return [
            'pages' => route('admin.plugins.vani-cms.pages.index'), 'posts' => route('admin.plugins.vani-cms.posts.index'),
            'index' => route("admin.plugins.vani-cms.{$kind}.index"), 'create' => route("admin.plugins.vani-cms.{$kind}.create"),
            'store' => route("admin.plugins.vani-cms.{$kind}.store"),
            'preview' => route('admin.plugins.vani-cms.preview'),
        ];
    }

    private function fill(Content $item, Request $request, string $kind): void
    {
        $table = $item->getTable();
        $request->merge(['slug' => Str::slug((string) ($request->input('slug') ?: $request->input('title')))]);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique($table, 'slug')->ignore($item->id)],
            'body' => ['required', 'string', 'max:200000'],
            'meta_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'status' => ['required', Rule::in([Content::DRAFT, Content::PUBLISHED])],
            'published_at' => ['nullable', 'date'],
            ...($kind === 'posts'
                ? ['excerpt' => ['nullable', 'string', 'max:500'], 'cover_media_id' => ['nullable', 'integer'], 'remove_cover' => ['boolean']]
                : ['show_in_header' => ['boolean'], 'show_in_footer' => ['boolean'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535']]),
        ]);

        $cover = isset($data['cover_media_id']) ? (int) $data['cover_media_id'] : null;
        $removeCover = (bool) ($data['remove_cover'] ?? false);
        unset($data['cover_media_id'], $data['remove_cover']);
        if ($cover !== null && $this->media->find([$cover]) === []) {
            throw ValidationException::withMessages(['cover_media_id' => 'Ảnh bìa không còn trong Thư viện ảnh.']);
        }

        // Giờ nhập theo giờ VN; đăng mà không chọn giờ → đăng ngay (giữ giờ đăng cũ nếu đã có).
        $publishedAt = isset($data['published_at']) ? Carbon::parse($data['published_at'], self::TIMEZONE)->utc() : null;
        if ($data['status'] === Content::PUBLISHED && $publishedAt === null) {
            $publishedAt = $item->published_at ?? now();
        }

        $item->fill([...$data, 'published_at' => $publishedAt, ...($kind === 'posts' ? [
            // Ảnh bìa từ thư viện thay ảnh bìa kiểu cũ (cover_path); không chọn gì → giữ ảnh cũ trừ khi bấm "Bỏ".
            'cover_media_id' => $removeCover ? null : $cover,
            ...($cover !== null || $removeCover ? ['cover_path' => null] : []),
        ] : []), ...($kind === 'pages' ? [
            'show_in_header' => $request->boolean('show_in_header'), 'show_in_footer' => $request->boolean('show_in_footer'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ] : [])]);
        DB::transaction(function () use ($item, $kind): void {
            $item->save();
            // Ảnh đang dùng (bìa + chèn trong bài) → Thư viện ảnh không cho xoá.
            $this->media->syncUsages($this->ownerType($kind), $item->id, 'body', Markdown::mediaIds($item->body));
            if ($item instanceof Post) {
                $this->media->syncUsages(CmsMedia::POST, $item->id, 'cover', $item->cover_media_id === null ? [] : [$item->cover_media_id]);
            }
        });
        Navigation::forget();
    }

    private function ownerType(string $kind): string
    {
        return $kind === 'pages' ? CmsMedia::PAGE : CmsMedia::POST;
    }

    private function status(Content $item): string
    {
        return match (true) {
            $item->status === Content::DRAFT => 'draft',
            $item->isPublic() => 'published',
            default => 'scheduled',
        };
    }

    private function publicUrl(string $kind, string $slug): string
    {
        return route($kind === 'pages' ? 'storefront.p.vani-cms.page' : 'storefront.p.vani-cms.post', $slug);
    }

    private function model(string $kind): Content
    {
        return $kind === 'pages' ? new Page : new Post;
    }

    private function find(string $kind, int $id): Content
    {
        return $this->model($kind)->newQuery()->findOrFail($id);
    }
}
