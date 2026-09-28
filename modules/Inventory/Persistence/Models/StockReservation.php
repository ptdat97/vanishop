<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Inventory\Domain\ReservationStatus;

/**
 * @property int $id
 * @property string $reservation_key
 * @property int $location_id
 * @property int $variant_id
 * @property int $quantity
 * @property ReservationStatus $status
 * @property Carbon|null $expires_at
 */
final class StockReservation extends Model
{
    protected $fillable = ['reservation_key', 'location_id', 'variant_id', 'quantity', 'status', 'expires_at', 'release_reason'];

    protected function casts(): array
    {
        return ['status' => ReservationStatus::class, 'expires_at' => 'datetime', 'quantity' => 'integer'];
    }
}
