<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $stock_count_id
 * @property int $variant_id
 * @property int $counted số đếm thực tế
 * @property int $on_hand_at_count tồn hệ thống lúc ghi số đếm
 * @property Carbon $counted_at
 * @property int|null $applied_delta chênh đã ghi vào tồn khi áp dụng
 */
final class StockCountLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['stock_count_id', 'variant_id', 'counted', 'on_hand_at_count', 'counted_at', 'applied_delta'];

    protected function casts(): array
    {
        return ['stock_count_id' => 'integer', 'variant_id' => 'integer', 'counted' => 'integer', 'on_hand_at_count' => 'integer', 'counted_at' => 'datetime', 'applied_delta' => 'integer'];
    }

    public function variance(): int
    {
        return $this->counted - $this->on_hand_at_count;
    }
}
