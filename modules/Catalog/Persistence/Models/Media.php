<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
