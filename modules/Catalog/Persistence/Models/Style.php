<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Catalog\Domain\PublishWindow;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Persistence\Database\Factories\StyleFactory;
use Modules\Shared\Persistence\Concerns\BelongsToBrand;
use Modules\Shared\Persistence\Concerns\HasTranslations;

/**
 * Style = sản phẩm khách nhìn thấy (một trang PDP). Biến thể màu × size ở StyleColor / Variant.
 *
 * @property int $id
 * @property int $brand_id
 * @property string $style_code
 * @property string $slug
 * @property StyleStatus $status
 * @property Carbon|null $published_from
 * @property Carbon|null $published_to
 * @property int|null $primary_category_id
 * @property string $search_text
 * @property array<string, mixed>|null $meta
 * @property int $lock_version
 */
final class Style extends Model
{
    use BelongsToBrand;

    /** @use HasFactory<StyleFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = ['brand_id', 'style_code', 'slug', 'status', 'published_from', 'published_to', 'primary_category_id', 'search_text', 'meta', 'lock_version'];

    protected function casts(): array
    {
        return [
            'status' => StyleStatus::class,
            'published_from' => 'datetime',
            'published_to' => 'datetime',
            'meta' => 'array',
            'lock_version' => 'integer',
        ];
    }

    protected function translationModel(): string
    {
        return StyleTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name', 'description', 'care_instructions', 'meta_title', 'meta_description'];
    }

    public function publishWindow(): PublishWindow
    {
        return new PublishWindow(
            $this->published_from === null ? null : DateTimeImmutable::createFromInterface($this->published_from),
            $this->published_to === null ? null : DateTimeImmutable::createFromInterface($this->published_to),
        );
    }

    /**
     * @return HasMany<StyleColor, $this>
     */
    public function colors(): HasMany
    {
        return $this->hasMany(StyleColor::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<Variant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('position');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function primaryCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'primary_category_id');
    }

    /**
     * @return HasMany<StyleAttributeValue, $this>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(StyleAttributeValue::class);
    }

    /**
     * @return BelongsToMany<ProductCollection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(ProductCollection::class, 'collection_style', 'style_id', 'collection_id')->withPivot('position');
    }

    protected static function newFactory(): StyleFactory
    {
        return StyleFactory::new();
    }
}
