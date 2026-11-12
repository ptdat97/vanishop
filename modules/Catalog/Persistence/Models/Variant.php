<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Modules\Catalog\Domain\VariantStatus;

/**
 * SKU: một tổ hợp giá trị tuỳ chọn của sản phẩm — đúng một giá trị cho mỗi trục (ADR-045). `option_key` = id giá trị theo
 * thứ tự trục, duy nhất theo sản phẩm. Ảnh theo SKU (`gallery`).
 *
 * @property int $id
 * @property int $style_id
 * @property string|null $option_key
 * @property string $sku
 * @property string|null $barcode
 * @property VariantStatus $status
 * @property int|null $weight_gram
 * @property int $lock_version
 * @property Collection<int, OptionValue> $optionValues
 * @property Collection<int, Mediable> $gallery
 */
final class Variant extends Model
{
    protected $fillable = ['style_id', 'option_key', 'sku', 'barcode', 'status', 'weight_gram', 'meta', 'lock_version'];

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
     * @return BelongsToMany<OptionValue, $this>
     */
    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(OptionValue::class, 'variant_option_values')->withPivot('option_type_id');
    }

    /**
     * @return MorphMany<Mediable, $this>
     */
    public function gallery(): MorphMany
    {
        return $this->morphMany(Mediable::class, 'mediable')->where('role', 'gallery')->orderBy('position')->orderBy('id');
    }

    /** Giá trị của SKU cho một loại tuỳ chọn (theo mã loại), null nếu sản phẩm không có trục đó. Cần nạp `optionValues.type`. */
    public function valueOf(string $typeCode): ?OptionValue
    {
        return $this->optionValues->first(fn (OptionValue $value): bool => $value->type->code === $typeCode);
    }

    /**
     * Khoá tổ hợp: id giá trị theo thứ tự trục của sản phẩm.
     *
     * @param  list<int>  $valueIds  theo thứ tự trục
     */
    public static function keyFor(array $valueIds): string
    {
        return implode('-', $valueIds);
    }
}
