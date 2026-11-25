<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Persistence\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Fulfillment\Domain\ShipmentStatus;

/**
 * @property int $id
 * @property string $public_id
 * @property int $order_id
 * @property int $location_id
 * @property string $carrier_code
 * @property string|null $service_code
 * @property string|null $tracking_number
 * @property int $cod_amount
 * @property int|null $actual_carrier_fee
 * @property string $currency_code
 * @property ShipmentStatus $status
 * @property int $booking_attempts
 * @property string|null $last_error lỗi đặt vận đơn gần nhất
 * @property CarbonImmutable|null $booking_started_at lease: lần đặt đang gọi hãng (L31)
 * @property string|null $booking_lease mã lease của lần đặt đang chạy
 * @property int $lock_version
 * @property CarbonImmutable|null $shipped_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable|null $delivery_failed_at
 */
final class Shipment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'order_id' => 'integer', 'location_id' => 'integer', 'cod_amount' => 'integer', 'actual_carrier_fee' => 'integer', 'status' => ShipmentStatus::class,
            'booking_attempts' => 'integer', 'lock_version' => 'integer', 'shipped_at' => 'immutable_datetime', 'delivered_at' => 'immutable_datetime',
            'booking_started_at' => 'immutable_datetime', 'delivery_failed_at' => 'immutable_datetime',
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
