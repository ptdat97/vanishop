<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Domain\VariantStatus;

/**
 * @property int $id
 * @property int $style_id
 * @property int $style_color_id
 * @property int $size_id
 * @property string $sku
 * @property string|null $barcode
 * @property VariantStatus $status
 * @property int|null $weight_gram
 * @property int $lock_version
 * @property StyleColor $styleColor
 * @property Size $size
 */
final class Variant extends Model
{
    protected $fillable = ['style_id', 'style_color_id', 'size_id', 'sku', 'barcode', 'status', 'weight_gram', 'meta', 'lock_version'];

    protected function casts(): array
    {
        return ['status' => VariantStatus::class, 'meta' => 'array', 'weight_gram' => 'integer', 'lock_version' => 'integer'];
    }

    /**
     * @return BelongsTo<Style, $this>
     */
    public function style(): BelongsTo
    {
        return $this->belongsTo(Style::class);
    }

    /**
     * @return BelongsTo<StyleColor, $this>
     */
    public function styleColor(): BelongsTo
    {
        return $this->belongsTo(StyleColor::class);
    }

    /**
     * @return BelongsTo<Size, $this>
     */
    public function size(): BelongsTo
    {
        return $this->belongsTo(Size::class);
    }
}
