<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use LogicException;
use Modules\Inventory\Domain\MovementType;

/**
 * Sổ biến động tồn — append-only.
 *
 * @property int $id
 * @property int $location_id
 * @property int $variant_id
 * @property MovementType $type
 * @property int $on_hand_delta
 * @property int $reserved_delta
 * @property int $on_hand_after
 * @property int $reserved_after
 * @property string|null $reason
 * @property string|null $reference
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string|null $correlation_id
 * @property CarbonImmutable $created_at
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
