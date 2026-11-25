<?php

declare(strict_types=1);

namespace Modules\Cart\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cart_id
 * @property int $variant_id
 * @property int $quantity
 * @property bool $selected
 * @property int|null $unit_price_snapshot
 * @property string $options_hash băm tuỳ chọn dòng (CartLineOption); cùng variant + cùng băm = cùng dòng
 * @property array<string, mixed>|null $meta
 */
final class CartLine extends Model
{
    protected $fillable = ['cart_id', 'variant_id', 'options_hash', 'quantity', 'selected', 'unit_price_snapshot', 'meta'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'selected' => 'boolean',
            'unit_price_snapshot' => 'integer',
            'meta' => 'array',
        ];
    }
}
