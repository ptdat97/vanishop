<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Catalog\Domain\Category\CategoryPath;
use Modules\Catalog\Domain\CategoryStatus;
use Modules\Catalog\Persistence\Database\Factories\CategoryFactory;
use Modules\Shared\Persistence\Concerns\BelongsToBrand;
use Modules\Shared\Persistence\Concerns\HasTranslations;

/**
 * @property int $id
 * @property int $brand_id
 * @property int|null $parent_id
 * @property string $slug
 * @property string $path
 * @property int $depth
 * @property int $position
 * @property CategoryStatus $status
 * @property int $lock_version
 */
final class Category extends Model
{
    use BelongsToBrand;

    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = ['brand_id', 'parent_id', 'slug', 'path', 'depth', 'position', 'status', 'lock_version'];

    protected function casts(): array
    {
        return ['status' => CategoryStatus::class, 'depth' => 'integer', 'position' => 'integer', 'lock_version' => 'integer'];
    }

    protected function translationModel(): string
    {
        return CategoryTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name', 'description', 'meta_title', 'meta_description'];
    }

    public function categoryPath(): CategoryPath
    {
        return CategoryPath::fromString($this->path);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<self, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    /**
     * @return MorphMany<Mediable, $this>
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Mediable::class, 'mediable')->orderBy('position');
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
