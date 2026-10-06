<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Một lần đối soát tồn kho (nội bộ hoặc với nguồn ngoài) — dùng chung cho
 * `vani:inventory:verify` và `vani:inventory:reconcile` (roadmap Phase 3).
 *
 * @property int $id
 * @property string $source
 * @property Carbon|null $window_from
 * @property Carbon|null $window_to
 * @property int $checked
 * @property int $discrepancies
 * @property int $repaired
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 */
final class InventoryReconciliation extends Model
{
    /** source của lần đối soát nội bộ (vani:inventory:verify). */
    public const SOURCE_INTERNAL_VERIFY = 'internal_verify';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'window_from' => 'immutable_datetime',
            'window_to' => 'immutable_datetime',
            'checked' => 'integer',
            'discrepancies' => 'integer',
            'repaired' => 'integer',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<InventoryReconciliationLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(InventoryReconciliationLine::class);
    }
}
