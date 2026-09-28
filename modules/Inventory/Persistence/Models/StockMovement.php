<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;
use Modules\Inventory\Domain\MovementType;

/**
 * Sổ biến động tồn — append-only.
 *
 * @property MovementType $type
 */
final class StockMovement extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['location_id', 'variant_id', 'type', 'on_hand_delta', 'reserved_delta', 'on_hand_after', 'reserved_after', 'reason', 'reference', 'actor_type', 'actor_id', 'correlation_id', 'created_at'];

    protected function casts(): array
    {
        return ['type' => MovementType::class, 'created_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('stock_movements là append-only.'));
        self::deleting(fn () => throw new LogicException('stock_movements là append-only.'));
    }
}
