<?php

declare(strict_types=1);

namespace Modules\Pricing\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Pricing\Domain\PriceListType;

/**
 * @property int $id
 * @property int|null $customer_group_id
 * @property string $code
 * @property string $name
 * @property string $currency_code
 * @property PriceListType $type
 * @property int $priority
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string $status
 * @property int $lock_version
 */
final class PriceList extends Model
{
    protected $fillable = ['code', 'name', 'currency_code', 'type', 'customer_group_id', 'priority', 'starts_at', 'ends_at', 'status', 'lock_version'];

    protected function casts(): array
    {
        return ['type' => PriceListType::class, 'priority' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'lock_version' => 'integer'];
    }

    /**
     * @return HasMany<Price, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}
