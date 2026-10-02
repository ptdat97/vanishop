<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Domain\LocationType;

/**
 * Kho / cửa hàng / điểm ảo của cửa hàng.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property LocationType $type
 * @property bool $ships_online_orders
 * @property bool $allows_pickup
 * @property bool $accepts_returns
 * @property string $stock_authority
 * @property int $priority
 * @property string $status
 * @property int $lock_version
 */
final class Location extends Model
{
    public const AUTHORITY_VANISHOP = 'vanishop';

    protected $fillable = ['code', 'name', 'type', 'address', 'province_code', 'ships_online_orders', 'allows_pickup', 'accepts_returns', 'stock_authority', 'priority', 'status', 'lock_version'];

    protected function casts(): array
    {
        return [
            'type' => LocationType::class,
            'ships_online_orders' => 'boolean',
            'allows_pickup' => 'boolean',
            'accepts_returns' => 'boolean',
            'priority' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    public function managesOnHand(): bool
    {
        return $this->stock_authority === self::AUTHORITY_VANISHOP;
    }
}
