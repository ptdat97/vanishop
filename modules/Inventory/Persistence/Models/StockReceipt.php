<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Phiếu nhập nhanh (stabilization A6).
 *
 * @property int $id
 * @property string $number
 * @property int $location_id
 * @property string|null $supplier
 * @property string|null $note
 * @property string $status draft | received | cancelled
 * @property string|null $created_by_type
 * @property int|null $created_by_id
 * @property Carbon|null $received_at
 * @property int $lock_version
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class StockReceipt extends Model
{
    public const DRAFT = 'draft';

    public const RECEIVED = 'received';

    public const CANCELLED = 'cancelled';

    protected $fillable = ['number', 'location_id', 'supplier', 'note', 'status', 'created_by_type', 'created_by_id', 'received_at', 'lock_version'];

    protected function casts(): array
    {
        return ['location_id' => 'integer', 'created_by_id' => 'integer', 'received_at' => 'datetime', 'lock_version' => 'integer'];
    }

    /**
     * @return HasMany<StockReceiptLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(StockReceiptLine::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
