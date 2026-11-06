<?php

declare(strict_types=1);

namespace Plugin\Warehouse\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Dòng hàng của một phiếu chuyển kho.
 *
 * @property int $id
 * @property int $stock_transfer_id
 * @property int $variant_id
 * @property int $quantity
 * @property int|null $received_quantity
 */
final class TransferLine extends Model
{
    protected $table = 'plg_warehouse_transfer_lines';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'stock_transfer_id' => 'integer',
            'variant_id' => 'integer',
            'quantity' => 'integer',
            'received_quantity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Transfer, $this>
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class, 'stock_transfer_id');
    }
}
