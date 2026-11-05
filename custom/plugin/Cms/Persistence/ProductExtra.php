<?php

declare(strict_types=1);

namespace Plugin\Cms\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * Nội dung thêm của một mẫu sản phẩm (1.5.0): điểm nổi bật theo ngôn ngữ (tối đa 8), ẩn hộp ưu đãi dùng chung.
 *
 * @property int $id
 * @property int $style_id
 * @property array<string, list<string>>|null $highlights
 * @property bool $hide_promo_box
 */
final class ProductExtra extends Model
{
    protected $table = 'plg_cms_product_extras';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['style_id' => 'integer', 'highlights' => 'array', 'hide_promo_box' => 'boolean'];
    }
}
