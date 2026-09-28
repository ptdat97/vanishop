<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Modules\Shared\Persistence\Concerns\BelongsToBrand;

/**
 * File media (ảnh) thuộc một brand; gắn vào đối tượng qua Mediable.
 *
 * @property int $id
 * @property int $brand_id
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
    use BelongsToBrand;

    protected $fillable = ['brand_id', 'disk', 'path', 'original_name', 'mime_type', 'size_bytes', 'width', 'height', 'checksum'];

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
