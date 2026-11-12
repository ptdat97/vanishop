<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Giá trị tuỳ chọn mà sản phẩm dùng, theo thứ tự riêng của sản phẩm (vd. màu nào hiện trước).
 *
 * @property int $id
 * @property int $style_id
 * @property int $option_value_id
 * @property int $position
 * @property OptionValue $value
 */
final class StyleOptionValue extends Model
{
    protected $fillable = ['style_id', 'option_value_id', 'position'];

    protected function casts(): array
    {
        return ['style_id' => 'integer', 'option_value_id' => 'integer', 'position' => 'integer'];
    }

    /**
     * @return BelongsTo<OptionValue, $this>
     */
    public function value(): BelongsTo
    {
        return $this->belongsTo(OptionValue::class, 'option_value_id');
    }
}
