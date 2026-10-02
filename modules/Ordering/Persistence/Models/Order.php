<?php

declare(strict_types=1);

namespace Modules\Ordering\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Ordering\Contracts\Data\OrderStatus;

/**
 * @property int $id
 * @property string $public_id
 * @property string $number
 * @property string $source
 * @property OrderStatus $order_status
 * @property string $payment_status
 * @property string $currency_code
 * @property int $total_amount
 * @property array<string, mixed> $customer_snapshot
 */
final class Order extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'order_status' => OrderStatus::class,
            'subtotal_amount' => 'integer', 'discount_amount' => 'integer', 'shipping_amount' => 'integer', 'tax_amount' => 'integer', 'total_amount' => 'integer',
            'customer_snapshot' => 'array', 'shipping_address' => 'array', 'shipping_method' => 'array', 'meta' => 'array',
            'lock_version' => 'integer', 'placed_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class)->orderBy('id');
    }

    /**
     * @return HasMany<OrderAdjustment, $this>
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(OrderAdjustment::class)->orderBy('id');
    }
}
