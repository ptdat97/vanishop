<?php

declare(strict_types=1);

namespace Plugin\Wishlist\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $style_id
 * @property int|null $price_amount giá thấp nhất lúc thêm / lần báo giảm giá gần nhất
 * @property bool $in_stock
 * @property Carbon|null $notified_stock_at
 * @property Carbon|null $notified_price_at
 * @property Carbon $created_at
 */
final class WishlistItem extends Model
{
    protected $table = 'plg_wishlist_items';

    protected $fillable = ['customer_id', 'style_id', 'price_amount', 'in_stock', 'notified_stock_at', 'notified_price_at'];

    protected function casts(): array
    {
        return ['price_amount' => 'integer', 'in_stock' => 'boolean', 'notified_stock_at' => 'datetime', 'notified_price_at' => 'datetime'];
    }
}
