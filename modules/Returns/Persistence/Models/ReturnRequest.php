<?php

declare(strict_types=1);

namespace Modules\Returns\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Returns\Domain\ReturnStatus;

/**
 * @property int $id
 * @property string $public_id
 * @property string $number
 * @property int $order_id
 * @property ReturnStatus $status
 * @property string $reason_code
 * @property int $refund_amount
 * @property int|null $refunded_amount
 * @property string $currency_code
 * @property int $lock_version
 */
final class ReturnRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'order_id' => 'integer', 'status' => ReturnStatus::class, 'refund_amount' => 'integer',
            'refunded_amount' => 'integer', 'lock_version' => 'integer', 'resolved_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<ReturnLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(ReturnLine::class)->orderBy('id');
    }
}
