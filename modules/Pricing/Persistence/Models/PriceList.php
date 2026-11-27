<?php

declare(strict_types=1);

namespace Modules\Pricing\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
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
 * @property string|null $managed_by
 * @property string|null $managed_key
 * @property string|null $managed_route
 */
final class PriceList extends Model
{
    protected $fillable = ['code', 'name', 'currency_code', 'type', 'customer_group_id', 'priority', 'starts_at', 'ends_at', 'status', 'lock_version',
        'managed_by', 'managed_key', 'managed_route'];

    protected function casts(): array
    {
        return ['type' => PriceListType::class, 'priority' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'lock_version' => 'integer'];
    }

    /** Do plugin quản lý qua ManagedPriceLists (0.5.115): Admin chỉ xem. */
    public function isManaged(): bool
    {
        return $this->managed_by !== null;
    }

    /** Màn hình sửa của plugin; route không còn (plugin tắt) → null. */
    public function managedUrl(): ?string
    {
        return $this->managed_route !== null && $this->managed_key !== null && Route::has($this->managed_route)
            ? route($this->managed_route, [$this->managed_key])
            : null;
    }

    /**
     * @return HasMany<Price, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }
}
