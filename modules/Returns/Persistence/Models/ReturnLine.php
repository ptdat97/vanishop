<?php

declare(strict_types=1);

namespace Modules\Returns\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class ReturnLine extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['return_request_id' => 'integer', 'order_line_id' => 'integer', 'variant_id' => 'integer', 'quantity' => 'integer', 'refund_amount' => 'integer', 'restock_location_id' => 'integer'];
    }
}
