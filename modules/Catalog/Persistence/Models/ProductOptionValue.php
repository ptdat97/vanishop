<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Giá trị tuỳ chọn mà sản phẩm dùng trên một trục, theo thứ tự riêng của sản phẩm (vd. màu nào hiện trước).
 *
 * @property int $id
 * @property int $product_option_id
 * @property int $option_value_id
 * @property int $position
 * @property OptionValue $value
 */
final class ProductOptionValue extends Model
{
    protected $fillable = ['product_option_id', 'option_value_id', 'position'];

    protected function casts(): array
    {
        return ['product_option_id' => 'integer', 'option_value_id' => 'integer', 'position' => 'integer'];
    }

    /**
     * @return BelongsTo<OptionValue, $this>
     */
    public function value(): BelongsTo
    {
        return $this->belongsTo(OptionValue::class, 'option_value_id');
    }
}
