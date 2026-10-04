<?php

declare(strict_types=1);

namespace Modules\Payment\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $public_id
 * @property int $payment_id
 * @property int $amount
 * @property string $status requested | processing | completed | failed
 * @property string $reason
 * @property string $idempotency_key
 * @property string|null $gateway_reference
 * @property string|null $requested_by_type
 * @property int|null $requested_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
final class Refund extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['payment_id' => 'integer', 'amount' => 'integer'];
    }
}
