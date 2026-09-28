<?php

declare(strict_types=1);

namespace Modules\Pricing\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $price_list_id
 * @property int $variant_id
 * @property int $amount
 * @property int|null $compare_at_amount
 * @property int $min_qty
 */
final class Price extends Model
{
    protected $fillable = ['price_list_id', 'variant_id', 'amount', 'compare_at_amount', 'min_qty'];

    protected function casts(): array
    {
        return ['amount' => 'integer', 'compare_at_amount' => 'integer', 'min_qty' => 'integer'];
    }
}
