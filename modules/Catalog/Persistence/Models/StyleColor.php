<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Màu của một style; có bộ ảnh riêng (mediables role = gallery). Phạm vi brand kế thừa từ style.
 *
 * @property int $id
 * @property int $style_id
 * @property int $color_id
 * @property int $position
 * @property Color $color
 */
final class StyleColor extends Model
{
    protected $fillable = ['style_id', 'color_id', 'position'];

    /**
     * @return BelongsTo<Color, $this>
     */
    public function color(): BelongsTo
    {
        return $this->belongsTo(Color::class);
    }

    /**
     * @return BelongsTo<Style, $this>
     */
    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    /**
     * @return HasMany<Variant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    /**
     * @return MorphMany<Mediable, $this>
     */
    public function gallery(): MorphMany
    {
        return $this->morphMany(Mediable::class, 'mediable')->where('role', 'gallery')->orderBy('position')->orderBy('id');
    }
}
