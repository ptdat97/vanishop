<?php

declare(strict_types=1);

namespace Modules\Cart\Persistence\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Cart\Domain\CartStatus;

/**
 * Giỏ của cửa hàng; chứa sản phẩm của mọi brand.
 *
 * @property int $id
 * @property string $public_id
 * @property string $token_hash
 * @property int|null $customer_id
 * @property string $currency_code
 * @property CartStatus $status
 * @property int $lock_version
 * @property CarbonImmutable $last_activity_at
 */
final class Cart extends Model
{
    protected $fillable = ['public_id', 'token_hash', 'customer_id', 'currency_code', 'status', 'meta', 'lock_version', 'last_activity_at'];

    protected function casts(): array
    {
        return [
            'status' => CartStatus::class,
            'customer_id' => 'integer',
            'meta' => 'array',
            'lock_version' => 'integer',
            'last_activity_at' => 'immutable_datetime',
            'abandoned_notified_at' => 'immutable_datetime',
        ];
    }

    /**
     * @return HasMany<CartLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(CartLine::class)->orderBy('id');
    }
}
