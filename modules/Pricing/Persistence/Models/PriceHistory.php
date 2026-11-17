<?php

declare(strict_types=1);

namespace Modules\Pricing\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Append-only.
 *
 * @property int $id
 * @property int $price_list_id
 * @property int $variant_id
 * @property int|null $old_amount
 * @property int|null $new_amount null = gỡ khỏi bảng giá
 * @property int|null $old_compare_at_amount
 * @property int|null $new_compare_at_amount
 * @property string|null $actor_type
 * @property int|null $actor_id
 * @property string|null $correlation_id
 * @property Carbon|null $created_at
 */
final class PriceHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'price_history';

    protected $fillable = ['price_list_id', 'variant_id', 'old_amount', 'new_amount', 'old_compare_at_amount', 'new_compare_at_amount', 'actor_type', 'actor_id', 'correlation_id', 'created_at'];

    protected function casts(): array
    {
        return [
            'price_list_id' => 'integer', 'variant_id' => 'integer', 'old_amount' => 'integer', 'new_amount' => 'integer',
            'old_compare_at_amount' => 'integer', 'new_compare_at_amount' => 'integer', 'actor_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('price_history là append-only.'));
        self::deleting(fn () => throw new LogicException('price_history là append-only.'));
    }
}
