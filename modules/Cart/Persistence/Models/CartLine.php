<?php

declare(strict_types=1);

namespace Modules\Cart\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cart_id
 * @property int $variant_id
 * @property int $brand_id
 * @property int $quantity
 * @property int|null $unit_price_snapshot
 */
final class CartLine extends Model
{
    protected $fillable = ['cart_id', 'variant_id', 'brand_id', 'quantity', 'unit_price_snapshot', 'meta'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price_snapshot' => 'integer',
            'meta' => 'array',
        ];
    }
}
