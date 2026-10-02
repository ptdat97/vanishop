<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Persistence\Database\Factories\BrandFactory;

/**
 * Thương hiệu — thuộc tính của sản phẩm trong catalog (ADR-028), không phải phạm vi dữ liệu.
 *
 * @property int $id
 * @property string $code
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string|null $logo_path
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string $status
 * @property int $position
 * @property int $lock_version
 */
final class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;

    public const ACTIVE = 'active';

    public const HIDDEN = 'hidden';

    protected $fillable = ['code', 'slug', 'name', 'description', 'logo_path', 'meta_title', 'meta_description', 'status', 'position', 'lock_version'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'lock_version' => 'integer'];
    }

    /**
     * @return HasMany<Style, $this>
     */
    public function styles(): HasMany
    {
        return $this->hasMany(Style::class);
    }

    protected static function newFactory(): BrandFactory
    {
        return BrandFactory::new();
    }
}
