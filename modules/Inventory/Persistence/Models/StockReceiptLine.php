<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $stock_receipt_id
 * @property int $variant_id
 * @property int $quantity
 */
final class StockReceiptLine extends Model
{
    public $timestamps = false;

    protected $fillable = ['stock_receipt_id', 'variant_id', 'quantity'];

    protected function casts(): array
    {
        return ['stock_receipt_id' => 'integer', 'variant_id' => 'integer', 'quantity' => 'integer'];
    }
}
