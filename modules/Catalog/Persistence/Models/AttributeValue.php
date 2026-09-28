<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Shared\Persistence\Concerns\HasTranslations;

/**
 * Giá trị của thuộc tính kiểu select/multiselect. Phạm vi brand kế thừa từ attribute cha.
 *
 * @property int $id
 * @property int $attribute_id
 * @property string $code
 * @property int $position
 */
final class AttributeValue extends Model
{
    use HasTranslations;

    protected $fillable = ['attribute_id', 'code', 'position'];

    /**
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    protected function translationModel(): string
    {
        return AttributeValueTranslation::class;
    }

    public function translatableFields(): array
    {
        return ['label'];
    }
}
