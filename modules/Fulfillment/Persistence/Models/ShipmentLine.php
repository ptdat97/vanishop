<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class ShipmentLine extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['shipment_id' => 'integer', 'order_line_id' => 'integer', 'variant_id' => 'integer', 'quantity' => 'integer'];
    }
}
