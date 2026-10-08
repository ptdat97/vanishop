<?php

declare(strict_types=1);

namespace Modules\Customer\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Customer\Domain\CustomerStatus;

/**
 * Khách hàng của cửa hàng. registered_at = null → profile ẩn của khách vãng lai.
 *
 * @property int $id
 * @property string $public_id
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $full_name
 * @property Carbon|null $birth_date
 * @property string|null $gender
 * @property CustomerStatus $status
 * @property Carbon|null $registered_at
 * @property Carbon|null $phone_verified_at
 * @property string|null $password
 * @property int|null $merged_into_id
 * @property int|null $customer_group_id nhóm khách (0.3.35)
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $last_login_at
 * @property Carbon|null $created_at
 */
final class Customer extends Model
{
    protected $fillable = [
        'public_id', 'phone', 'email', 'full_name', 'birth_date', 'gender', 'status', 'registered_at', 'phone_verified_at',
        'password', 'merged_into_id', 'meta', 'last_login_at', 'orders_count', 'total_spent', 'first_order_at', 'last_order_at',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'status' => CustomerStatus::class, 'birth_date' => 'date', 'registered_at' => 'datetime', 'phone_verified_at' => 'datetime',
            'password' => 'hashed', 'merged_into_id' => 'integer', 'meta' => 'array', 'last_login_at' => 'datetime',
            'orders_count' => 'integer', 'total_spent' => 'integer', 'first_order_at' => 'datetime', 'last_order_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<CustomerAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function isActive(): bool
    {
        return $this->status === CustomerStatus::Active;
    }

    public function isRegistered(): bool
    {
        return $this->registered_at !== null;
    }
}
