<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Persistence\Database\Factories\OptionValueFactory;
use Modules\Shared\Persistence\Concerns\HasTranslations;

/**
 * Giá trị của một loại tuỳ chọn (ADR-045). `group`: nhóm giá trị (vd. hệ size); `hex`, `family`: cho loại swatch (ô màu,
 * lọc theo nhóm màu).
 *
 * @property int $id
 * @property int $option_type_id
 * @property string $code
 * @property string|null $group
 * @property string|null $hex
 * @property string|null $family
 * @property int $position
 * @property OptionType $type
 */
final class OptionValue extends Model
{
    /** @use HasFactory<OptionValueFactory> */
    use HasFactory;

    use HasTranslations;

    protected $fillable = ['option_type_id', 'code', 'group', 'hex', 'family', 'position'];

    protected function casts(): array
    {
        return ['option_type_id' => 'integer', 'position' => 'integer'];
    }

    protected function translationModel(): string
    {
        return OptionValueTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['name'];
    }

    /** Tên hiển thị: bản dịch, không có thì mã. */
    public function label(?string $locale = null): string
    {
        return $this->translate('name', $locale) ?? $this->code;
    }

    /**
     * @return BelongsTo<OptionType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(OptionType::class, 'option_type_id');
    }

    protected static function newFactory(): OptionValueFactory
    {
        return OptionValueFactory::new();
    }
}
