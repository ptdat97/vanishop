<?php

declare(strict_types=1);

namespace Modules\Pricing\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Append-only.
 */
final class PriceHistory extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'price_history';

    protected $fillable = ['price_list_id', 'variant_id', 'old_amount', 'new_amount', 'old_compare_at_amount', 'new_compare_at_amount', 'actor_type', 'actor_id', 'correlation_id', 'created_at'];

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('price_history là append-only.'));
        self::deleting(fn () => throw new LogicException('price_history là append-only.'));
    }
}
