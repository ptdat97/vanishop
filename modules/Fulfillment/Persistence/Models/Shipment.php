<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Persistence\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Shared\Persistence\Concerns\BelongsToBrand;

/**
 * @property int $id
 * @property string $public_id
 * @property int $order_id
 * @property int $brand_id
 * @property int $location_id
 * @property string $carrier_code
 * @property string|null $service_code
 * @property string|null $tracking_number
 * @property int $cod_amount
 * @property string $currency_code
 * @property ShipmentStatus $status
 * @property int $booking_attempts
 * @property int $lock_version
 * @property CarbonImmutable|null $delivered_at
 */
final class Shipment extends Model
{
    use BelongsToBrand;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'order_id' => 'integer', 'brand_id' => 'integer', 'location_id' => 'integer', 'cod_amount' => 'integer', 'status' => ShipmentStatus::class,
            'booking_attempts' => 'integer', 'lock_version' => 'integer', 'shipped_at' => 'immutable_datetime', 'delivered_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<ShipmentLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ShipmentLine::class)->orderBy('id');
    }
}
