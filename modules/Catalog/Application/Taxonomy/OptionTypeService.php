<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Taxonomy;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Domain\ColorFamily;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Danh mục loại tuỳ chọn dùng chung và giá trị của chúng (ADR-045). Không xoá loại/giá trị đang được sản phẩm dùng.
 */
final class OptionTypeService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{code: string, display_type: string, is_filterable: bool, position: int, translations: array<string, array{name: string}>}  $data
     */
    public function saveType(array $data, ?OptionType $type = null): OptionType
    {
        return DB::transaction(function () use ($data, $type): OptionType {
            $type ??= new OptionType;
            $type->fill(['code' => $data['code'], 'display_type' => $data['display_type'], 'is_filterable' => $data['is_filterable'], 'position' => $data['position']])->save();
            $type->syncTranslations($data['translations']);
            $this->audit->record($type->wasRecentlyCreated ? 'catalog.option_type.created' : 'catalog.option_type.updated', 'option_type', $type->id, ['code' => $type->code]);

            return $type->refresh();
        });
    }

    /**
     * Số sản phẩm dùng mỗi loại (làm trục) và mỗi giá trị — màn hình danh mục tuỳ chọn.
     *
     * @return array{types: array<int, int>, values: array<int, int>}
     */
    public function usage(): array
    {
        $count = fn (string $table, string $column): array => DB::table($table)->select($column, DB::raw('count(*) as total'))->groupBy($column)
            ->pluck('total', $column)->mapWithKeys(fn ($total, $id): array => [(int) $id => (int) $total])->all();

        return ['types' => $count('style_options', 'option_type_id'), 'values' => $count('style_option_values', 'option_value_id')];
    }

    public function deleteType(OptionType $type): void
    {
        if (DB::table('style_options')->where('option_type_id', $type->id)->exists()) {
            throw ValidationException::withMessages(['option_type' => __('catalog::messages.option_type_in_use')]);
        }
        DB::transaction(function () use ($type): void {
            OptionValue::query()->where('option_type_id', $type->id)->delete();
            $type->delete();
        });
        $this->audit->record('catalog.option_type.deleted', 'option_type', $type->id, ['code' => $type->code]);
    }

    /**
     * @param  array{code: string, group: string|null, hex: string|null, family: string|null, position: int, translations: array<string, array{name: string}>}  $data
     */
    public function saveValue(OptionType $type, array $data, ?OptionValue $value = null): OptionValue
    {
        return DB::transaction(function () use ($type, $data, $value): OptionValue {
            $value ??= new OptionValue(['option_type_id' => $type->id]);
            $value->fill([
                'code' => $data['code'], 'group' => $data['group'], 'hex' => $data['hex'] === null ? null : strtoupper($data['hex']),
                // Không chọn nhóm màu mà có mã hex → nhóm gần nhất (vẫn lọc được ở danh mục).
                'family' => $data['family'] ?? ($data['hex'] === null ? null : ColorFamily::nearest($data['hex'])?->value), 'position' => $data['position'],
            ])->save();
            $value->syncTranslations($data['translations']);
            $this->audit->record($value->wasRecentlyCreated ? 'catalog.option_value.created' : 'catalog.option_value.updated', 'option_value', $value->id, ['type' => $type->code, 'code' => $value->code]);

            return $value->refresh();
        });
    }

    public function deleteValue(OptionValue $value): void
    {
        if (DB::table('style_option_values')->where('option_value_id', $value->id)->exists()) {
            throw ValidationException::withMessages(['option_value' => __('catalog::messages.option_value_in_use_by_products')]);
        }
        $value->delete();
        $this->audit->record('catalog.option_value.deleted', 'option_value', $value->id, ['code' => $value->code]);
    }
}
