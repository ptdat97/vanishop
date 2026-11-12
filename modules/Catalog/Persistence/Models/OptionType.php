<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\Domain\OptionDisplayType;
use Modules\Catalog\Persistence\Database\Factories\OptionTypeFactory;
use Modules\Shared\Persistence\Concerns\HasTranslations;

/**
 * Loại tuỳ chọn dùng chung (ADR-045): Màu, Size, Chất liệu… — trục biến thể mà sản phẩm chọn dùng.
 *
 * @property int $id
 * @property string $code
 * @property OptionDisplayType $display_type
 * @property bool $is_filterable
 * @property int $position
 */
final class OptionType extends Model
{
    /** @use HasFactory<OptionTypeFactory> */
    use HasFactory;

    use HasTranslations;

    public const COLOR = 'color';

    public const SIZE = 'size';

    protected $fillable = ['code', 'display_type', 'is_filterable', 'position'];

    protected function casts(): array
    {
        return ['display_type' => OptionDisplayType::class, 'is_filterable' => 'boolean', 'position' => 'integer'];
    }

    protected function translationModel(): string
    {
        return OptionTypeTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name'];
    }

    /**
     * @return HasMany<OptionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(OptionValue::class)->orderBy('group')->orderBy('position')->orderBy('id');
    }

    protected static function newFactory(): OptionTypeFactory
    {
        return OptionTypeFactory::new();
    }
}
