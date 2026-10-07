<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Media;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Persistence\Models\Mediable;

/**
 * Lưu file ảnh (khử trùng lặp theo checksum) và gắn vào đối tượng theo vai trò. Ảnh thu nhỏ không tạo lúc tải lên mà
 * khi có request đầu tiên (ImageCache, public/cache).
 */
final class MediaLibrary
{
    public function __construct(
        private readonly string $disk,
        private readonly ImageCache $cache,
    ) {}

    /**
     * Ảnh trùng nội dung với ảnh đã có → trả ảnh cũ (giữ thư mục/tên của ảnh cũ).
     */
    public function store(UploadedFile $file, string $folder = ''): Media
    {
        $checksum = hash_file('sha256', (string) $file->getRealPath());

        $existing = Media::query()->where('checksum', $checksum)->first();
        if ($existing !== null) {
            return $existing;
        }

        $dimensions = @getimagesize((string) $file->getRealPath()) ?: [null, null];
        $path = $file->storeAs('media/'.substr($checksum, 0, 2), $checksum.'.'.$file->extension(), $this->disk);

        return Media::query()->create([
            'disk' => $this->disk,
            'path' => $path,
            'folder' => $folder,
            'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
            'mime_type' => (string) $file->getMimeType(),
            'size_bytes' => (int) $file->getSize(),
            'width' => $dimensions[0],
            'height' => $dimensions[1],
            'checksum' => $checksum,
        ]);
    }

    /**
     * Gắn một media duy nhất cho vai trò (thay thế media cũ cùng vai trò).
     */
    public function attachSingle(Model $owner, string $role, Media $media, ?string $alt = null): Mediable
    {
        return DB::transaction(function () use ($owner, $role, $media, $alt): Mediable {
            $owner->morphMany(Mediable::class, 'mediable')->where('role', $role)->delete();

            return $owner->morphMany(Mediable::class, 'mediable')->create([
                'media_id' => $media->id,
                'role' => $role,
                'position' => 0,
                'alt' => $alt,
            ]);
        });
    }

    public function detach(Model $owner, string $role): void
    {
        $owner->morphMany(Mediable::class, 'mediable')->where('role', $role)->delete();
    }

    public function url(Media $media): string
    {
        return Storage::disk($media->disk)->url($media->path);
    }

    /**
     * Xoá hẳn ảnh: bản ghi, file gốc, bản thu nhỏ trong cache. FK mediables (restrict) chặn xoá ảnh đang được dùng.
     */
    public function delete(Media $media): void
    {
        $media->delete();
        Storage::disk($media->disk)->delete($media->path);
        $this->cache->forget($media);
    }
}
