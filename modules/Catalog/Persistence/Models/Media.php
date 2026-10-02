<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Media\ImageVariants;

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
     * URL ảnh; có `$width` → bản WebP nhỏ nhất rộng ≥ $width (ImageVariants), không có thì ảnh gốc.
     */
    public function url(?int $width = null): string
    {
        if ($width !== null && $this->width !== null) {
            foreach (ImageVariants::widthsFor($this->width) as $variant) {
                if ($variant >= $width) {
                    return Storage::disk($this->disk)->url(ImageVariants::path($this->checksum, $variant));
                }
            }
        }

        return Storage::disk($this->disk)->url($this->path);
    }
}
