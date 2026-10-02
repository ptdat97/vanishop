<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Taxonomy;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Application\StaleRecord;
use Modules\Catalog\Domain\AttributeInputType;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\Size;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Thuộc tính, màu, size của cửa hàng (context kiểu CRUD — xem docs/02-architecture/bounded-contexts.md §4).
 */
final class TaxonomyService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{code: string, kind: string, input_type: string, is_filterable: bool, position: int, translations: array<string, array{name: string}>, values?: list<array{code: string, translations: array<string, array{label: string}>}>}  $data
     */
    public function saveAttribute(array $data, ?Attribute $attribute = null, ?int $expectedLockVersion = null): Attribute
    {
        $inputType = AttributeInputType::from($data['input_type']);
        $values = $data['values'] ?? [];

        if ($inputType->hasOptions() && $values === []) {
            throw ValidationException::withMessages(['values' => __('catalog::messages.attribute_values_required')]);
        }

        return DB::transaction(function () use ($data, $attribute, $expectedLockVersion, $inputType, $values): Attribute {
            if ($attribute !== null) {
                $updated = Attribute::query()->whereKey($attribute->id)->where('lock_version', $expectedLockVersion)->increment('lock_version');
                if ($updated === 0) {
                    throw new StaleRecord;
                }
            }

            $attribute ??= new Attribute;
            $attribute->fill([
                'code' => $data['code'],
                'kind' => $data['kind'],
                'input_type' => $inputType,
                'is_filterable' => $data['is_filterable'],
                'position' => $data['position'],
            ])->save();
            $attribute->syncTranslations($data['translations']);

            $this->syncValues($attribute, $inputType->hasOptions() ? $values : []);

            $this->audit->record($attribute->wasRecentlyCreated ? 'catalog.attribute.created' : 'catalog.attribute.updated', 'attribute', $attribute->id, ['code' => $attribute->code]);

            return $attribute->refresh();
        });
    }

    public function deleteAttribute(Attribute $attribute): void
    {
        DB::transaction(function () use ($attribute): void {
            $attribute->delete();
            $this->audit->record('catalog.attribute.deleted', 'attribute', $attribute->id, ['code' => $attribute->code]);
        });
    }

    /**
     * @param  array{code: string, color_family: string, hex: string|null, position: int, translations: array<string, array{name: string}>}  $data
     */
    public function saveColor(array $data, ?Color $color = null): Color
    {
        return DB::transaction(function () use ($data, $color): Color {
            $color ??= new Color;
            $color->fill([
                'code' => $data['code'],
                'color_family' => $data['color_family'],
                'hex' => $data['hex'] === null ? null : strtoupper($data['hex']),
                'position' => $data['position'],
            ])->save();
            $color->syncTranslations($data['translations']);

            $this->audit->record($color->wasRecentlyCreated ? 'catalog.color.created' : 'catalog.color.updated', 'color', $color->id, ['code' => $color->code]);

            return $color->refresh();
        });
    }

    /**
     * @param  array{code: string, slug: string, name: string, description: string|null, status: string, position: int}  $data
     */
    public function saveBrand(array $data, ?Brand $brand = null): Brand
    {
        $brand ??= new Brand;
        $isNew = ! $brand->exists;
        $brand->fill($data)->save();
        $this->audit->record($isNew ? 'catalog.brand.created' : 'catalog.brand.updated', 'brand', $brand->id, ['code' => $brand->code]);

        return $brand;
    }

    /**
     * Không xoá brand còn sản phẩm (ẩn bằng status = hidden).
     */
    public function deleteBrand(Brand $brand): void
    {
        if ($brand->styles()->exists()) {
            throw ValidationException::withMessages(['brand' => __('catalog::messages.brand_in_use')]);
        }

        $brand->delete();
        $this->audit->record('catalog.brand.deleted', 'brand', $brand->id, ['code' => $brand->code]);
    }

    public function deleteColor(Color $color): void
    {
        $color->delete();
        $this->audit->record('catalog.color.deleted', 'color', $color->id, ['code' => $color->code]);
    }

    /**
     * @param  array{size_system: string, code: string, sort_order: int}  $data
     */
    public function saveSize(array $data, ?Size $size = null): Size
    {
        $size ??= new Size;
        $size->fill($data)->save();

        $this->audit->record($size->wasRecentlyCreated ? 'catalog.size.created' : 'catalog.size.updated', 'size', $size->id, ['code' => $size->code]);

        return $size;
    }

    public function deleteSize(Size $size): void
    {
        $size->delete();
        $this->audit->record('catalog.size.deleted', 'size', $size->id, ['code' => $size->code]);
    }

    /**
     * Đồng bộ giá trị theo code: giữ id của giá trị còn tồn tại (để dữ liệu sản phẩm tham chiếu không bị mất).
     *
     * @param  list<array{code: string, translations: array<string, array{label: string}>}>  $values
     */
    private function syncValues(Attribute $attribute, array $values): void
    {
        $codes = array_column($values, 'code');
        $attribute->values()->whereNotIn('code', $codes)->delete();

        foreach (array_values($values) as $position => $value) {
            $model = $attribute->values()->updateOrCreate(['code' => $value['code']], ['position' => $position]);
            $model->syncTranslations($value['translations']);
        }
    }
}
