<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Media;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Persistence\Models\MediaFolder;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Thư viện ảnh dùng chung (Admin): thư mục ảo, tải lên, đổi tên, chuyển thư mục, xoá ảnh không còn dùng.
 *
 * Thư mục chỉ là nhãn trong DB (file thật nằm theo checksum) nên không có rủi ro duyệt đường dẫn trên filesystem như
 * file manager dạng thư mục thật; đường dẫn vẫn giới hạn vào slug a-z0-9- để hiển thị và lọc ổn định.
 */
final class MediaManager
{
    public const MAX_DEPTH = 4;

    public const PER_PAGE = 40;

    public const SORTS = ['created_at', 'original_name', 'size_bytes'];

    public function __construct(
        private readonly MediaLibrary $library,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return list<string> mọi thư mục, theo thứ tự đường dẫn (cha đứng trước con)
     */
    public function folders(): array
    {
        return MediaFolder::query()->orderBy('path')->pluck('path')->all();
    }

    /**
     * Có từ khoá → tìm theo tên trong mọi thư mục; không có → ảnh của thư mục đang mở (không gồm thư mục con).
     *
     * @param  array{folder?: string|null, keyword?: string|null, sort?: string|null, order?: string|null}  $filters
     * @return LengthAwarePaginator<int, Media>
     */
    public function search(array $filters, int $page = 1): LengthAwarePaginator
    {
        $sort = in_array($filters['sort'] ?? null, self::SORTS, true) ? (string) $filters['sort'] : 'created_at';
        $order = ($filters['order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $keyword = trim((string) ($filters['keyword'] ?? ''));

        return Media::query()
            ->withCount('usages')
            ->when(
                $keyword !== '',
                fn (Builder $query) => $query->where('original_name', 'like', '%'.addcslashes($keyword, '%_\\').'%'),
                fn (Builder $query) => $query->where('folder', (string) ($filters['folder'] ?? '')),
            )
            ->orderBy($sort, $order)
            ->orderBy('id', $order)
            ->paginate(self::PER_PAGE, page: max(1, $page));
    }

    public function createFolder(string $parent, string $name): string
    {
        $this->ensureFolderExists($parent, 'parent');
        $path = $this->childPath($parent, $name);
        if (MediaFolder::query()->where('path', $path)->exists()) {
            throw ValidationException::withMessages(['name' => __('catalog::messages.media_folder_exists')]);
        }

        MediaFolder::query()->create(['path' => $path]);
        $this->audit->record('catalog.media.folder_created', 'media_folder', null, ['path' => $path]);

        return $path;
    }

    /**
     * Đổi tên đoạn cuối của thư mục; thư mục con và ảnh bên trong đi theo.
     */
    public function renameFolder(string $path, string $name): string
    {
        $this->ensureFolderExists($path, 'folder', allowRoot: false);
        $newPath = $this->childPath(Str::contains($path, '/') ? Str::beforeLast($path, '/') : '', $name);
        if ($newPath === $path) {
            return $path;
        }
        if (MediaFolder::query()->where('path', $newPath)->exists()) {
            throw ValidationException::withMessages(['name' => __('catalog::messages.media_folder_exists')]);
        }

        DB::transaction(function () use ($path, $newPath): void {
            $rename = fn (string $current): string => $newPath.substr($current, strlen($path));
            foreach (MediaFolder::query()->where('path', $path)->orWhere('path', 'like', $path.'/%')->lockForUpdate()->get() as $folder) {
                $folder->update(['path' => $rename($folder->path)]);
            }
            foreach (Media::query()->where('folder', $path)->orWhere('folder', 'like', $path.'/%')->get(['id', 'folder']) as $media) {
                $media->update(['folder' => $rename($media->folder)]);
            }
        });
        $this->audit->record('catalog.media.folder_renamed', 'media_folder', null, ['from' => $path, 'to' => $newPath]);

        return $newPath;
    }

    /**
     * Chỉ xoá thư mục rỗng (không thư mục con, không ảnh) — tránh xoá nhầm cả loạt ảnh.
     */
    public function deleteFolder(string $path): void
    {
        $this->ensureFolderExists($path, 'folder', allowRoot: false);
        if (MediaFolder::query()->where('path', 'like', $path.'/%')->exists() || Media::query()->where('folder', $path)->exists()) {
            throw ValidationException::withMessages(['folder' => __('catalog::messages.media_folder_not_empty')]);
        }

        MediaFolder::query()->where('path', $path)->delete();
        $this->audit->record('catalog.media.folder_deleted', 'media_folder', null, ['path' => $path]);
    }

    /**
     * @param  list<UploadedFile>  $files
     * @return list<Media> theo thứ tự tải lên (ảnh trùng nội dung trả về ảnh đã có)
     */
    public function upload(array $files, string $folder): array
    {
        $this->ensureFolderExists($folder, 'folder');

        $stored = array_map(fn (UploadedFile $file): Media => $this->library->store($file, $folder), $files);
        $created = count(array_filter($stored, fn (Media $media): bool => $media->wasRecentlyCreated));
        $this->audit->record('catalog.media.uploaded', 'media_folder', null, ['folder' => $folder, 'created' => $created, 'duplicates' => count($stored) - $created]);

        return $stored;
    }

    public function rename(Media $media, string $name): void
    {
        $media->update(['original_name' => trim($name)]);
        $this->audit->record('catalog.media.renamed', 'media', $media->id, ['name' => $media->original_name]);
    }

    /**
     * @param  list<int>  $ids
     */
    public function move(array $ids, string $folder): int
    {
        $this->ensureFolderExists($folder, 'folder');
        $moved = Media::query()->whereKey($ids)->update(['folder' => $folder]);
        $this->audit->record('catalog.media.moved', 'media_folder', null, ['folder' => $folder, 'ids' => $ids]);

        return $moved;
    }

    /**
     * Xoá ảnh không còn được dùng; ảnh đang gắn vào sản phẩm/danh mục được bỏ qua và báo lại.
     *
     * @param  list<int>  $ids
     * @return array{deleted: int, in_use: int}
     */
    public function delete(array $ids): array
    {
        $items = Media::query()->whereKey($ids)->withCount('usages')->get();
        $deletable = $items->filter(fn (Media $media): bool => (int) $media->getAttribute('usages_count') === 0);

        foreach ($deletable as $media) {
            $this->library->delete($media);
        }
        if ($deletable->isNotEmpty()) {
            $this->audit->record('catalog.media.deleted', 'media', null, ['ids' => $deletable->modelKeys()]);
        }

        return ['deleted' => $deletable->count(), 'in_use' => $items->count() - $deletable->count()];
    }

    private function childPath(string $parent, string $name): string
    {
        $segment = Str::slug($name);
        if ($segment === '') {
            throw ValidationException::withMessages(['name' => __('catalog::messages.media_folder_name_invalid')]);
        }
        $path = $parent === '' ? $segment : "{$parent}/{$segment}";
        if (substr_count($path, '/') + 1 > self::MAX_DEPTH || strlen($path) > 191) {
            throw ValidationException::withMessages(['name' => __('catalog::messages.media_folder_too_deep', ['max' => self::MAX_DEPTH])]);
        }

        return $path;
    }

    private function ensureFolderExists(string $path, string $field, bool $allowRoot = true): void
    {
        $exists = $path === '' ? $allowRoot : MediaFolder::query()->where('path', $path)->exists();
        if (! $exists) {
            throw ValidationException::withMessages([$field => __('catalog::messages.media_folder_not_found')]);
        }
    }
}
