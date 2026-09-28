<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Domain\AttributeInputType;
use Modules\Catalog\Domain\AttributeKind;
use Modules\Catalog\Persistence\Database\Factories\AttributeFactory;
use Modules\Shared\Persistence\Concerns\BelongsToBrand;
use Modules\Shared\Persistence\Concerns\HasTranslations;

/**
 * @property int $id
 * @property int $brand_id
 * @property string $code
 * @property AttributeKind $kind
 * @property AttributeInputType $input_type
 * @property bool $is_filterable
 * @property int $position
 * @property int $lock_version
 */
final class Attribute extends Model
{
    use BelongsToBrand;

    /** @use HasFactory<AttributeFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = ['brand_id', 'code', 'kind', 'input_type', 'is_filterable', 'position', 'lock_version'];

    protected function casts(): array
    {
        return [
            'kind' => AttributeKind::class,
            'input_type' => AttributeInputType::class,
            'is_filterable' => 'boolean',
            'position' => 'integer',
            'lock_version' => 'integer',
        ];
    }

    protected function translationModel(): string
    {
        return AttributeTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name'];
    }

    /**
     * @return HasMany<AttributeValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('position')->orderBy('id');
    }

    protected static function newFactory(): AttributeFactory
    {
        return AttributeFactory::new();
    }
}
