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
 * @property-read bool|null $in_product_gallery chỉ có khi query `withExists(['usages as in_product_gallery' => …])`
 */
final class Media extends Model
{
    protected $fillable = ['disk', 'path', 'folder', 'original_name', 'mime_type', 'size_bytes', 'width', 'height', 'checksum'];

    /**
     * URL ảnh; `$size` số → bản thu nhỏ rộng ≥ số đó trong public/cache (ImageCache), chuỗi → kích thước có tên (plugin);
     * `$group` = `product` → cắt theo tỉ lệ ảnh sản phẩm (Admin, mặc định 2:3). Không có bản phù hợp → ảnh gốc.
     */
    public function url(int|string|null $size = null, ?string $group = null): string
    {
        return app(ImageCache::class)->url($this, $size, $group);
    }

    /**
     * `srcset` cho ảnh responsive (0.3.67); rỗng khi ảnh không có bản thu nhỏ. `$group` như url().
     */
    public function srcset(?string $group = null): string
    {
        return app(ImageCache::class)->srcset($this, $group);
    }

    public const VIDEO_TYPES = ['video/mp4' => 'mp4', 'video/webm' => 'webm'];

    /**
     * MIME chuẩn hoá khi lưu: một số bản libmagic nhận MP4 là `application/mp4`.
     */
    public static function normalizeMime(string $mimeType): string
    {
        return $mimeType === 'application/mp4' ? 'video/mp4' : $mimeType;
    }

    /**
     * Video (0.3.91): không có bản thu nhỏ, luôn dùng file gốc.
     */
    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    /**
     * @return 'image'|'video'
     */
    public function kind(): string
    {
        return $this->isVideo() ? 'video' : 'image';
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
