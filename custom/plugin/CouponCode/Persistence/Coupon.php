<?php

declare(strict_types=1);

namespace Plugin\CouponCode\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Shared\Support\StoreLocale;

/**
 * @property int $id
 * @property int|null $promotion_id
 * @property string $kind shared | template
 * @property string|null $code
 * @property string|null $prefix
 * @property string $name
 * @property array<string, array{description?: string}> $translations
 * @property string $discount_type percent | amount
 * @property int $discount_value
 * @property int|null $max_discount
 * @property int|null $min_subtotal
 * @property string $scope_type
 * @property list<int> $scope_values
 * @property list<string> $scope_slugs
 * @property bool $exclude_on_sale
 * @property list<string> $customer_groups
 * @property int|null $usage_limit
 * @property int|null $usage_per_customer
 * @property int|null $valid_days
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $active
 * @property bool $exclusive
 * @property bool $public
 * @property int $lock_version
 */
final class Coupon extends Model
{
    public const KIND_SHARED = 'shared';

    public const KIND_TEMPLATE = 'template';

    protected $table = 'plg_coupons';

    protected $fillable = ['promotion_id', 'kind', 'code', 'prefix', 'name', 'translations', 'discount_type', 'discount_value', 'max_discount', 'min_subtotal',
        'scope_type', 'scope_values', 'scope_slugs', 'exclude_on_sale', 'customer_groups', 'usage_limit', 'usage_per_customer', 'valid_days', 'starts_at', 'ends_at',
        'active', 'exclusive', 'public', 'lock_version'];

    protected function casts(): array
    {
        return [
            'promotion_id' => 'integer', 'translations' => 'array', 'discount_value' => 'integer', 'max_discount' => 'integer', 'min_subtotal' => 'integer',
            'scope_values' => 'array', 'scope_slugs' => 'array', 'exclude_on_sale' => 'boolean', 'customer_groups' => 'array', 'usage_limit' => 'integer', 'usage_per_customer' => 'integer',
            'valid_days' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'active' => 'boolean', 'exclusive' => 'boolean', 'public' => 'boolean',
            'lock_version' => 'integer',
        ];
    }

    /** Mô tả cho khách theo ngôn ngữ, thiếu thì lấy ngôn ngữ mặc định. */
    public function description(string $locale): string
    {
        return (string) ($this->translations[$locale]['description'] ?? $this->translations[StoreLocale::default()]['description'] ?? '');
    }

    public function isRunningAt(int $now): bool
    {
        return $this->active
            && ($this->starts_at === null || $this->starts_at->getTimestamp() <= $now)
            && ($this->ends_at === null || $this->ends_at->getTimestamp() > $now);
    }
}
