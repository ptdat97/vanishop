<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Inventory\Domain\TransferStatus;

/**
 * Chuyển hàng giữa hai location (docs/08-inventory/inventory.md §6).
 *
 * @property int $id
 * @property string $public_id
 * @property int $from_location_id
 * @property int $to_location_id
 * @property TransferStatus $status
 * @property string|null $note
 * @property string|null $reference
 * @property string|null $cancel_reason
 * @property int $lock_version
 * @property CarbonImmutable|null $shipped_at
 * @property CarbonImmutable|null $received_at
 * @property CarbonImmutable|null $cancelled_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class StockTransfer extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'from_location_id' => 'integer',
            'to_location_id' => 'integer',
            'status' => TransferStatus::class,
            'lock_version' => 'integer',
            'shipped_at' => 'immutable_datetime',
            'received_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<StockTransferLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(StockTransferLine::class)->orderBy('id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    /**
     * @return BelongsTo<Location, $this>
     */
    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }
}
