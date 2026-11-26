<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Domain\ProductStatus;
use Modules\Catalog\Domain\PublishWindow;
use Modules\Catalog\Persistence\Concerns\HasLocalizedSlug;
use Modules\Catalog\Persistence\Concerns\RecordsSlugRedirects;
use Modules\Catalog\Persistence\Database\Factories\ProductFactory;
use Modules\Shared\Persistence\Concerns\HasTranslations;

/**
 * Product = sản phẩm khách nhìn thấy (một trang PDP). Biến thể: 1–3 trục tuỳ chọn (ProductOption) và SKU theo tổ hợp (Variant, ADR-045).
 *
 * @property int $id
 * @property int|null $brand_id
 * @property string $product_code
 * @property string $slug
 * @property ProductStatus $status
 * @property Carbon|null $published_from
 * @property Carbon|null $published_to
 * @property int|null $primary_category_id
 * @property string $search_text
 * @property array<string, mixed>|null $meta
 * @property int $lock_version
 * @property bool $track_inventory
 */
final class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasLocalizedSlug;
    use HasTranslations;
    use RecordsSlugRedirects;

    /** Loại bản ghi trong `slug_redirects` (đổi slug → link cũ chuyển hướng 301). */
    public const SLUG_SUBJECT = CatalogReader::SLUG_PRODUCT;

    protected $fillable = ['brand_id', 'product_code', 'slug', 'status', 'published_from', 'published_to', 'primary_category_id', 'search_text', 'meta', 'lock_version', 'track_inventory'];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'track_inventory' => 'boolean',
            'published_from' => 'datetime',
            'published_to' => 'datetime',
            'meta' => 'array',
            'lock_version' => 'integer',
        ];
    }

    protected function translationModel(): string
    {
        return ProductTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name', 'description', 'care_instructions', 'meta_title', 'meta_description', 'slug'];
    }

    public function publishWindow(): PublishWindow
    {
        return new PublishWindow(
            $this->published_from === null ? null : DateTimeImmutable::createFromInterface($this->published_from),
            $this->published_to === null ? null : DateTimeImmutable::createFromInterface($this->published_to),
        );
    }

    /**
     * @return BelongsTo<Brand, $this>
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    /**
     * Trục biến thể của sản phẩm theo thứ tự (1–3, ADR-045).
     *
     * @return HasMany<ProductOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Giá trị tuỳ chọn sản phẩm dùng (mọi trục), theo thứ tự riêng của sản phẩm. Chỉ đọc — ghi qua ProductOptions.
     *
     * @return HasManyThrough<ProductOptionValue, ProductOption, $this>
     */
    public function optionValues(): HasManyThrough
    {
        return $this->hasManyThrough(ProductOptionValue::class, ProductOption::class)
            ->orderBy('product_option_values.position')->orderBy('product_option_values.id');
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
     * @return HasMany<ProductAttributeValue, $this>
     */
    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class);
    }

    /**
     * @return BelongsToMany<ProductCollection, $this>
     */
    public function collections(): BelongsToMany
    {
        return $this->belongsToMany(ProductCollection::class, 'collection_product', 'product_id', 'collection_id')->withPivot('position');
    }

    /**
     * @return BelongsToMany<Video, $this>
     */
    public function videos(): BelongsToMany
    {
        return $this->belongsToMany(Video::class)->withPivot('position')->orderByPivot('position')->orderBy('videos.id');
    }

    protected static function newFactory(): ProductFactory
    {
        return ProductFactory::new();
    }
}
