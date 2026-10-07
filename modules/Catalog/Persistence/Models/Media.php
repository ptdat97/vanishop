<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Catalog\Application\Media\ImageCache;

/**
 * File media (ảnh); gắn vào đối tượng qua Mediable. `folder` là thư mục ảo của Thư viện ảnh (file thật luôn nằm theo
 * checksum); `original_name` là tên hiển thị, đổi được.
 *
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $folder
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 * @property int|null $width
 * @property int|null $height
 * @property string $checksum
 * @property Carbon $created_at
 */
final class Media extends Model
{
    protected $fillable = ['disk', 'path', 'folder', 'original_name', 'mime_type', 'size_bytes', 'width', 'height', 'checksum'];

    /**
     * URL ảnh; có `$width` → bản thu nhỏ rộng ≥ $width trong public/cache (ImageCache), không có thì ảnh gốc.
     */
    public function url(?int $width = null): string
    {
        return app(ImageCache::class)->url($this, $width);
    }

    /**
     * Nơi đang dùng ảnh (màu sản phẩm, danh mục…).
     *
     * @return HasMany<Mediable, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(Mediable::class);
    }
}
