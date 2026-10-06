<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Một chênh lệch tồn kho trong một lần đối soát.
 *
 * Phân loại (classification):
 * - `external_mismatch`: nguồn ngoài (snapshot) ≠ VaniShop;
 * - `reserved_mismatch`: hàng giữ active ≠ reserved;
 * - `reserved_off_ledger`: reserved lệch sổ biến động;
 * - `on_hand_off_ledger`: tồn bị sửa ngoài sổ biến động (cần kiểm kê);
 * - `negative_on_hand`: tồn âm.
 *
 * @property int $id
 * @property int $reconciliation_id
 * @property int $location_id
 * @property int $variant_id
 * @property string $classification
 * @property int $expected
 * @property int $actual
 * @property int $difference
 * @property string|null $note
 * @property Carbon $detected_at
 * @property Carbon|null $resolved_at
 * @property string|null $resolution
 */
final class InventoryReconciliationLine extends Model
{
    public const CLASSIFICATION_EXTERNAL_MISMATCH = 'external_mismatch';

    public const RESOLUTION_REPAIRED = 'repaired';

    public const RESOLUTION_APPLIED = 'applied';

    public const RESOLUTION_REJECTED = 'rejected';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'reconciliation_id' => 'integer',
            'location_id' => 'integer',
            'variant_id' => 'integer',
            'expected' => 'integer',
            'actual' => 'integer',
            'difference' => 'integer',
            'detected_at' => 'immutable_datetime',
            'resolved_at' => 'immutable_datetime',
        ];
    }
}
