<?php

declare(strict_types=1);

namespace Modules\Ordering\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class OrderLine extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'variant_id' => 'integer', 'quantity' => 'integer', 'unit_amount' => 'integer', 'compare_at_amount' => 'integer', 'subtotal_amount' => 'integer',
            'discount_amount' => 'integer', 'total_amount' => 'integer', 'tax_rate_bp' => 'integer', 'tax_amount' => 'integer',
            'meta' => 'array',
        ];
    }
}
