<?php

declare(strict_types=1);

namespace Plugin\Bundles\Persistence;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Shared\Support\StoreLocale;

/**
 * @property int $id
 * @property string $slug
 * @property array<string, array{name?: string, description?: string}> $translations
 * @property int|null $image_media_id
 * @property int $discount_basis_points
 * @property bool $active
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int $position
 */
final class Bundle extends Model
{
    protected $table = 'plg_bundles';

    protected $fillable = ['slug', 'translations', 'image_media_id', 'discount_basis_points', 'active', 'starts_at', 'ends_at', 'position'];

    protected function casts(): array
    {
        return [
            'translations' => 'array', 'image_media_id' => 'integer', 'discount_basis_points' => 'integer', 'active' => 'boolean',
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'position' => 'integer',
        ];
    }

    /**
     * @return HasMany<BundleItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(BundleItem::class)->orderBy('position');
    }

    /** Chuỗi theo ngôn ngữ, thiếu thì lấy ngôn ngữ mặc định của cửa hàng. */
    public function text(string $field, string $locale): string
    {
        return (string) ($this->translations[$locale][$field] ?? $this->translations[StoreLocale::default()][$field] ?? '');
    }

    public function isRunningAt(int $now): bool
    {
        return $this->active
            && ($this->starts_at === null || $this->starts_at->getTimestamp() <= $now)
            && ($this->ends_at === null || $this->ends_at->getTimestamp() > $now);
    }

    /**
     * @return array<int, int> product id => số lượng mỗi bộ
     */
    public function requirement(): array
    {
        return $this->items->mapWithKeys(fn (BundleItem $item): array => [$item->product_id => $item->quantity])->all();
    }
}
