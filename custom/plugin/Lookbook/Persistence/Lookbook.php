<?php

declare(strict_types=1);

namespace Plugin\Lookbook\Persistence;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Shared\Support\StoreLocale;

/**
 * @property int $id
 * @property string $slug
 * @property array<string, array{name?: string|null, description?: string|null, meta_title?: string|null, meta_description?: string|null}> $translations
 * @property int|null $cover_media_id
 * @property int|null $category_id
 * @property int $position
 * @property bool $active
 * @property int $lock_version
 */
final class Lookbook extends Model
{
    protected $table = 'plg_lookbooks';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['translations' => 'array', 'cover_media_id' => 'integer', 'category_id' => 'integer', 'position' => 'integer', 'active' => 'boolean', 'lock_version' => 'integer'];
    }

    /**
     * @return HasMany<LookbookImage, $this>
     */
    public function images(): HasMany
    {
        return $this->hasMany(LookbookImage::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('active', true);
    }

    /** Trường theo ngôn ngữ, thiếu thì ngôn ngữ mặc định (tiêu chí §8.4). */
    public function text(string $field, string $locale): ?string
    {
        $value = $this->translations[$locale][$field] ?? null;
        if ($value === null || trim((string) $value) === '') {
            $value = $this->translations[StoreLocale::default()][$field] ?? null;
        }

        return $value === null || trim((string) $value) === '' ? null : (string) $value;
    }

    /** @return list<string> ngôn ngữ có tên (bản dịch) */
    public function translatedLocales(): array
    {
        return array_values(array_filter(StoreLocale::supported(), fn (string $locale): bool => trim((string) ($this->translations[$locale]['name'] ?? '')) !== ''));
    }
}
