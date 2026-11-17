<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Phiên kiểm kê nhanh (stabilization A7).
 *
 * @property int $id
 * @property string $number
 * @property int $location_id
 * @property string|null $note
 * @property string $status counting | applied | cancelled
 * @property string|null $created_by_type
 * @property int|null $created_by_id
 * @property Carbon|null $applied_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class StockCount extends Model
{
    public const COUNTING = 'counting';

    public const APPLIED = 'applied';

    public const CANCELLED = 'cancelled';

    protected $fillable = ['number', 'location_id', 'note', 'status', 'created_by_type', 'created_by_id', 'applied_at'];

    protected function casts(): array
    {
        return ['location_id' => 'integer', 'created_by_id' => 'integer', 'applied_at' => 'datetime'];
    }

    /**
     * @return HasMany<StockCountLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(StockCountLine::class)->orderByDesc('counted_at')->orderByDesc('id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
