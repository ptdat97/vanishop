<?php

declare(strict_types=1);

namespace Modules\Ordering\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Điều chỉnh tiền của đơn (snapshot): promotion, cancellation, promotion_clawback, exchange_credit…
 *
 * @property int $id
 * @property int $order_id
 * @property string $type
 * @property string $source
 * @property string|null $code
 * @property string $label
 * @property int $amount
 * @property array<string, mixed>|null $meta
 */
final class OrderAdjustment extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'meta' => 'array'];
    }
}
