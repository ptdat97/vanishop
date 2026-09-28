<?php

declare(strict_types=1);

namespace Modules\Payment\Persistence\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Shared\Persistence\Concerns\BelongsToBrand;

/**
 * @property int $id
 * @property string $public_id
 * @property int $order_id
 * @property int $legal_entity_id
 * @property int $brand_id
 * @property string $gateway_code
 * @property int $amount
 * @property int $refunded_amount
 * @property string $currency_code
 * @property PaymentStatus $status
 * @property string|null $gateway_reference
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $paid_at
 * @property int $lock_version
 */
final class Payment extends Model
{
    use BelongsToBrand;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'order_id' => 'integer', 'legal_entity_id' => 'integer', 'brand_id' => 'integer', 'amount' => 'integer', 'refunded_amount' => 'integer',
            'status' => PaymentStatus::class, 'expires_at' => 'immutable_datetime', 'paid_at' => 'immutable_datetime', 'meta' => 'array', 'lock_version' => 'integer',
        ];
    }
}
