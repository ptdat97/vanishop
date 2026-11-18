<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Slug cũ → bản ghi đang dùng (sản phẩm, danh mục, thương hiệu) để chuyển hướng 301.
 *
 * @property int $id
 * @property string $subject
 * @property int $subject_id
 * @property string $slug
 */
final class SlugRedirect extends Model
{
    protected $fillable = ['subject', 'subject_id', 'slug'];

    protected function casts(): array
    {
        return ['subject_id' => 'integer'];
    }
}
