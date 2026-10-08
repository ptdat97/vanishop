<?php

declare(strict_types=1);

namespace Modules\Ordering\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $order_id
 * @property int $variant_id
 * @property string $sku
 * @property string $product_name
 * @property int|null $brand_id
 * @property string|null $brand_name
 * @property string|null $color_name
 * @property string $size_code
 * @property string|null $image_url
 * @property int $quantity
 * @property int $cancelled_quantity
 * @property string|null $price_list_code bảng giá của giá bán (0.3.37)
 * @property int $unit_amount
 * @property int|null $compare_at_amount
 * @property int $subtotal_amount
 * @property int $discount_amount
 * @property int $total_amount
 * @property int $tax_rate_bp
 * @property int $tax_amount
 * @property array<string, mixed>|null $meta
 */
final class OrderLine extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'variant_id' => 'integer', 'quantity' => 'integer', 'cancelled_quantity' => 'integer', 'unit_amount' => 'integer', 'compare_at_amount' => 'integer', 'subtotal_amount' => 'integer',
            'discount_amount' => 'integer', 'total_amount' => 'integer', 'tax_rate_bp' => 'integer', 'tax_amount' => 'integer',
            'meta' => 'array',
        ];
    }
}
