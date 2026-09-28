<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $style_id
 * @property int $attribute_id
 * @property int|null $attribute_value_id
 * @property string|null $value_text
 * @property bool|null $value_bool
 * @property Attribute $attribute
 * @property AttributeValue|null $value
 */
final class StyleAttributeValue extends Model
{
    public $timestamps = false;

    protected $fillable = ['style_id', 'attribute_id', 'attribute_value_id', 'value_text', 'value_bool'];

    protected function casts(): array
    {
        return ['value_bool' => 'boolean'];
    }

    /**
     * @return BelongsTo<Attribute, $this>
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * @return BelongsTo<AttributeValue, $this>
     */
    public function value(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class, 'attribute_value_id');
    }
}
