<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trục biến thể của sản phẩm (ADR-045): loại tuỳ chọn + thứ tự (tối đa 3 trục).
 *
 * @property int $id
 * @property int $product_id
 * @property int $option_type_id
 * @property int $position
 * @property OptionType $type
 */
final class ProductOption extends Model
{
    protected $fillable = ['product_id', 'option_type_id', 'position'];

    protected function casts(): array
    {
        return ['product_id' => 'integer', 'option_type_id' => 'integer', 'position' => 'integer'];
    }

    /**
     * @return BelongsTo<OptionType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(OptionType::class, 'option_type_id');
    }
}
