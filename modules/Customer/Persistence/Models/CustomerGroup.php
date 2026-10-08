<?php

declare(strict_types=1);

namespace Modules\Customer\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nhóm khách (VIP, nhân viên, sỉ…) — quyết định bảng giá thành viên (`price_lists.customer_group_id`).
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string|null $description
 * @property int $position
 */
final class CustomerGroup extends Model
{
    protected $fillable = ['code', 'name', 'description', 'position'];

    /**
     * @return HasMany<Customer, $this>
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
