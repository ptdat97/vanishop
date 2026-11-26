<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $product_link_group_id
 * @property int $product_id
 * @property int $position
 * @property ProductLinkGroup $group
 * @property Product $product
 */
final class ProductLinkGroupItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['product_link_group_id', 'product_id', 'position'];

    protected function casts(): array
    {
        return ['product_link_group_id' => 'integer', 'product_id' => 'integer', 'position' => 'integer'];
    }

    /**
     * @return BelongsTo<ProductLinkGroup, $this>
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductLinkGroup::class, 'product_link_group_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
