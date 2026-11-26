<?php

declare(strict_types=1);

namespace Plugin\Bundles\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $bundle_id
 * @property int $product_id
 * @property int $quantity
 * @property int $position
 */
final class BundleItem extends Model
{
    public $timestamps = false;

    protected $table = 'plg_bundle_items';

    protected $fillable = ['bundle_id', 'product_id', 'quantity', 'position'];

    protected function casts(): array
    {
        return ['bundle_id' => 'integer', 'product_id' => 'integer', 'quantity' => 'integer', 'position' => 'integer'];
    }
}
