<?php

declare(strict_types=1);

namespace Modules\Returns\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $return_request_id
 * @property int $order_line_id
 * @property int $variant_id
 * @property int|null $exchange_variant_id variant thay thế khi đổi hàng (0.3.32)
 * @property int $quantity
 * @property int $refund_amount
 * @property string|null $condition sellable | damaged
 * @property int|null $restock_location_id
 */
final class ReturnLine extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['return_request_id' => 'integer', 'order_line_id' => 'integer', 'variant_id' => 'integer', 'quantity' => 'integer', 'refund_amount' => 'integer', 'restock_location_id' => 'integer', 'exchange_variant_id' => 'integer'];
    }
}
