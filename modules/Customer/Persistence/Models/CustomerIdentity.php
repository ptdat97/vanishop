<?php

declare(strict_types=1);

namespace Modules\Customer\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $customer_id
 * @property string $provider
 * @property string $subject
 */
final class CustomerIdentity extends Model
{
    protected $fillable = ['customer_id', 'provider', 'subject', 'last_used_at'];

    protected function casts(): array
    {
        return ['customer_id' => 'integer', 'last_used_at' => 'immutable_datetime'];
    }
}
