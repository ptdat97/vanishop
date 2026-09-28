<?php

declare(strict_types=1);

namespace Modules\Inventory\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Inventory\Domain\StockLevel;

/**
 * @property int $id
 * @property int $location_id
 * @property int $variant_id
 * @property int $on_hand
 * @property int $reserved
 * @property int $safety_stock
 * @property int|null $sync_version
 */
final class StockLevelRecord extends Model
{
    protected $table = 'stock_levels';

    protected $fillable = ['location_id', 'variant_id', 'on_hand', 'reserved', 'safety_stock', 'sync_version'];

    protected function casts(): array
    {
        return ['on_hand' => 'integer', 'reserved' => 'integer', 'safety_stock' => 'integer', 'sync_version' => 'integer'];
    }

    public function toDomain(): StockLevel
    {
        return new StockLevel($this->location_id, $this->variant_id, $this->on_hand, $this->reserved, $this->safety_stock, $this->sync_version);
    }

    public function fillFromDomain(StockLevel $level): self
    {
        $this->forceFill([
            'on_hand' => $level->onHand,
            'reserved' => $level->reserved,
            'safety_stock' => $level->safetyStock,
            'sync_version' => $level->syncVersion,
        ]);

        return $this;
    }
}
