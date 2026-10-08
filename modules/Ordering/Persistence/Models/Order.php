<?php

declare(strict_types=1);

namespace Modules\Ordering\Persistence\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Ordering\Contracts\Data\OrderStatus;

/**
 * @property int $id
 * @property string $public_id
 * @property string $number
 * @property string $source
 * @property int|null $customer_id
 * @property string $currency_code
 * @property OrderStatus $order_status
 * @property string $payment_status
 * @property string $fulfillment_status
 * @property string $return_status
 * @property string $payment_method
 * @property int $subtotal_amount
 * @property int $discount_amount
 * @property int $shipping_amount
 * @property int $tax_amount
 * @property int $total_amount
 * @property array<string, mixed> $customer_snapshot
 * @property string|null $customer_phone
 * @property string|null $access_token_hash
 * @property array<string, string> $shipping_address
 * @property array<string, mixed> $shipping_method
 * @property string|null $note
 * @property string $reservation_key
 * @property string|null $source_cart_id
 * @property int|null $parent_order_id
 * @property array<string, mixed>|null $meta
 * @property int $lock_version
 * @property CarbonImmutable $placed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
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
