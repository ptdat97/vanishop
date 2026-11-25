<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Catalog\Persistence\Concerns\HasLocalizedSlug;
use Modules\Shared\Persistence\Concerns\HasTranslations;
use Modules\Shared\Support\StoreLocale;

/**
 * Bộ sưu tập thủ công (landing page, campaign). Bộ sưu tập theo luật: Designed.
 *
 * @property int $id
 * @property string $slug
 * @property string $status
 * @property int $position
 * @property int|null $image_media_id
 * @property int $lock_version
 */
final class ProductCollection extends Model
{
    use HasLocalizedSlug;
    use HasTranslations;

    protected $table = 'collections';

    protected $fillable = ['slug', 'status', 'position', 'image_media_id'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'lock_version' => 'integer'];
    }

    protected function translationModel(): string
    {
        return ProductCollectionTranslation::class;
    }

    protected function translationForeignKey(): ?string
    {
        return 'collection_id';
    }

    public function translatableFields(): array
    {
        return ['name', 'description', 'slug', 'meta_title', 'meta_description'];
    }

    /** @param Builder<static> $query */
    public function scopeWherePublishedSlug(Builder $query, string $slug, string $locale): void
    {
        $query->where('status', 'active')->whereLocalizedSlug($slug, $locale);
        if ($locale !== StoreLocale::default()) {
            $query->orderByRaw('CASE WHEN collections.slug = ? THEN 1 ELSE 0 END', [$slug]);
        }
        $query->orderBy('id');
    }

    /**
     * @return BelongsToMany<Style, $this>
     */
    public function styles(): BelongsToMany
    {
        return $this->belongsToMany(Style::class, 'collection_style', 'collection_id', 'style_id')->withPivot('position')->orderByPivot('position');
    }
}
