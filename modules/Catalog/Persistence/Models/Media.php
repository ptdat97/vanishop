<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Catalog\Application\Media\ImageCache;

/**
 * File media (ảnh); gắn vào đối tượng qua Mediable.
 *
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 * @property int|null $width
 * @property int|null $height
 * @property string $checksum
 */
final class Media extends Model
{
    protected $fillable = ['disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'width', 'height', 'checksum'];

    /**
     * URL ảnh; có `$width` → bản thu nhỏ rộng ≥ $width trong public/cache (ImageCache), không có thì ảnh gốc.
     */
    public function url(?int $width = null): string
    {
        return app(ImageCache::class)->url($this, $width);
    }
}
