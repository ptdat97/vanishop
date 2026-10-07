<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Media\MediaManager;
use Modules\Catalog\Persistence\Models\Media;

/**
 * Thư viện ảnh dùng chung: trang quản lý (Inertia) + API JSON cho chính trang đó và modal chọn ảnh (MediaPicker) ở các
 * form sản phẩm, danh mục… Xem ảnh/chọn ảnh: `media.view`; tải lên, sắp xếp, xoá: `media.manage`.
 */
final class MediaLibraryController
{
    public const UPLOAD_TYPES = ['jpg', 'jpeg', 'png', 'webp'];

    public const MAX_UPLOAD_KB = 10 * 1024;

    public const MAX_FILES_PER_UPLOAD = 20;

    public function index(): Response
    {
        Gate::authorize('media.view');

        return Inertia::render('Catalog::Media/Index', [
            'libraryUrl' => route('admin.media.index'),
            'canManage' => Gate::allows('media.manage'),
        ]);
    }

    public function browse(Request $request, MediaManager $manager): JsonResponse
    {
        Gate::authorize('media.view');
        $filters = $request->validate([
            'folder' => ['nullable', 'string', 'max:191'],
            'keyword' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'string'],
            'order' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $page = $manager->search($filters, (int) ($filters['page'] ?? 1));

        return response()->json([
            'folders' => $manager->folders(),
            'items' => array_map(fn (Media $item): array => $this->present($item), $page->items()),
            'meta' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'total' => $page->total()],
            'can_manage' => Gate::allows('media.manage'),
        ]);
    }

    public function createFolder(Request $request, MediaManager $manager): JsonResponse
    {
        Gate::authorize('media.manage');
        $data = $request->validate(['parent' => ['nullable', 'string', 'max:191'], 'name' => ['required', 'string', 'max:60']]);

        return response()->json(['path' => $manager->createFolder((string) ($data['parent'] ?? ''), $data['name'])]);
    }

    public function renameFolder(Request $request, MediaManager $manager): JsonResponse
    {
        Gate::authorize('media.manage');
        $data = $request->validate(['path' => ['required', 'string', 'max:191'], 'name' => ['required', 'string', 'max:60']]);

        return response()->json(['path' => $manager->renameFolder($data['path'], $data['name'])]);
    }

    public function deleteFolder(Request $request, MediaManager $manager): JsonResponse
    {
        Gate::authorize('media.manage');
        $data = $request->validate(['path' => ['required', 'string', 'max:191']]);
        $manager->deleteFolder($data['path']);

        return response()->json(['message' => __('catalog::messages.deleted')]);
    }

    public function upload(Request $request, MediaManager $manager): JsonResponse
    {
        Gate::authorize('media.manage');
        $data = $request->validate([
            'folder' => ['nullable', 'string', 'max:191'],
            'images' => ['required', 'array', 'min:1', 'max:'.self::MAX_FILES_PER_UPLOAD],
            'images.*' => [File::image()->types(self::UPLOAD_TYPES)->max(self::MAX_UPLOAD_KB)],
        ]);
        $stored = $manager->upload(array_values($request->file('images', [])), (string) ($data['folder'] ?? ''));

        return response()->json([
            'message' => __('catalog::messages.media_uploaded', ['count' => count($stored)]),
            'items' => array_map(fn (Media $item): array => $this->present($item->loadCount('usages')), $stored),
        ]);
    }

    public function rename(Media $media, Request $request, MediaManager $manager): JsonResponse
    {
        Gate::authorize('media.manage');
        $data = $request->validate(['name' => ['required', 'string', 'max:255']]);
        $manager->rename($media, $data['name']);

        return response()->json(['item' => $this->present($media->loadCount('usages'))]);
    }

    public function move(Request $request, MediaManager $manager): JsonResponse
    {
        Gate::authorize('media.manage');
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer'], 'folder' => ['nullable', 'string', 'max:191']]);
        $moved = $manager->move(array_map('intval', $data['ids']), (string) ($data['folder'] ?? ''));

        return response()->json(['message' => __('catalog::messages.media_moved', ['count' => $moved])]);
    }

    public function destroy(Request $request, MediaManager $manager): JsonResponse
    {
        Gate::authorize('media.manage');
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:200'], 'ids.*' => ['integer']]);
        $result = $manager->delete(array_map('intval', $data['ids']));

        $message = __('catalog::messages.media_deleted', ['deleted' => $result['deleted']]);
        if ($result['in_use'] > 0) {
            $message .= ' '.__('catalog::messages.media_in_use_skipped', ['count' => $result['in_use']]);
        }

        return response()->json($result + ['message' => $message]);
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Media $item): array
    {
        return [
            'id' => $item->id,
            'name' => $item->original_name,
            'thumb_url' => $item->url(400),
            'url' => $item->url(),
            'width' => $item->width,
            'height' => $item->height,
            'size_bytes' => $item->size_bytes,
            'mime_type' => $item->mime_type,
            'folder' => $item->folder,
            'usages_count' => (int) $item->getAttribute('usages_count'),
            'created_at' => $item->created_at->toIso8601String(),
        ];
    }
}
