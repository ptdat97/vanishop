<?php

declare(strict_types=1);

namespace Modules\Customer\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $token_hash
 * @property string|null $name
 * @property Carbon|null $last_used_at
 * @property Carbon $expires_at
 * @property-read Customer $customer
 */
final class CustomerToken extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['customer_id', 'token_hash', 'name', 'last_used_at', 'expires_at'];

    protected function casts(): array
    {
        return ['customer_id' => 'integer', 'last_used_at' => 'datetime', 'expires_at' => 'datetime'];
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
