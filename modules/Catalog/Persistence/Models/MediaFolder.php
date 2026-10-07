<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Thư mục ảo của Thư viện ảnh ("san-pham/ao-thun"). Chỉ là nhãn để sắp xếp; không ánh xạ ra filesystem.
 *
 * @property int $id
 * @property string $path
 */
final class MediaFolder extends Model
{
    protected $fillable = ['path'];
}
