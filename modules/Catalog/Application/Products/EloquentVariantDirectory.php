<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Shared\Support\StoreLocale;

final class EloquentVariantDirectory implements VariantDirectory
{
    public function ofStyleCode(string $styleCode): array
    {
        return Variant::query()
            ->whereHas('style', fn ($query) => $query->where('style_code', $styleCode))
            ->get()
            ->tap(fn ($variants) => VariantOptions::load($variants))
            ->sortBy(fn (Variant $variant): string => VariantOptions::sortKey($variant))
            ->map(fn (Variant $variant): VariantData => $this->toData($variant))
            ->values()
            ->all();
    }

    public function ofStyles(array $styleIds): array
    {
        $sorted = $this->read('style_id', $styleIds);
        uasort($sorted, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);
        $result = [];
        foreach ($sorted as $row) {
            $result[$row['data']->styleId][] = $row['data'];
        }

        return $result;
    }

    public function find(array $variantIds): array
    {
        return array_map(fn (array $row): VariantData => $row['data'], $this->read('id', $variantIds));
    }

    public function findBySkus(array $skus): array
    {
        $result = [];
        foreach ($this->read('sku', array_values(array_unique($skus))) as $row) {
            $result[$row['data']->sku] = $row['data'];
        }

        return $result;
    }

    /**
     * Đường đọc hàng loạt (danh sách sản phẩm, lưới tồn, bảng giá, file xuất): dựng VariantData thẳng từ vài truy vấn, không
     * qua model. Đo 2026-11-16 (MySQL, 3.000 SKU): qua Eloquent 1,8 s (≈ 0,6 ms/SKU, phần lớn là dựng model và quan hệ).
     * Kết quả giống `toData()` của model: tên theo locale hiện tại → ngôn ngữ mặc định → bản dịch đầu tiên; tuỳ chọn theo thứ
     * tự trục của sản phẩm; khoá sắp xếp như VariantOptions::sortKey.
     *
     * @param  'id'|'style_id'|'sku'  $column
     * @param  list<int|string>  $keys
     * @return array<int, array{data: VariantData, sort: string}> theo id SKU
     */
    private function read(string $column, array $keys): array
    {
        if ($keys === []) {
            return [];
        }

        $variants = collect();
        foreach (array_chunk(array_values(array_unique($keys)), 1000) as $chunk) {
            $variants = $variants->concat(DB::table('variants as v')->join('styles as s', 's.id', '=', 'v.style_id')->whereIn("v.{$column}", $chunk)
                ->get(['v.id', 'v.style_id', 'v.sku', 'v.status', 's.brand_id', 's.style_code', 's.track_inventory']));
        }
        if ($variants->isEmpty()) {
            return [];
        }

        $variantIds = $variants->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $styleIds = $variants->pluck('style_id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
        $locale = app()->getLocale();
        $fallback = StoreLocale::default();
        $pick = function ($translations) use ($locale, $fallback): ?string {
            $translation = $translations->firstWhere('locale', $locale) ?? $translations->firstWhere('locale', $fallback) ?? $translations->first();

            return $translation?->name === null ? null : (string) $translation->name;
        };

        $names = DB::table('style_translations')->whereIn('style_id', $styleIds)->orderBy('id')->get(['style_id', 'locale', 'name'])->groupBy('style_id')->map($pick);
        $axes = DB::table('style_options')->whereIn('style_id', $styleIds)->orderBy('position')->orderBy('id')->get(['style_id', 'option_type_id'])
            ->groupBy('style_id')->map(fn ($rows): array => $rows->pluck('option_type_id')->map(fn ($id): int => (int) $id)->all());
        $positions = [];
        foreach (DB::table('style_option_values')->whereIn('style_id', $styleIds)->orderBy('position')->orderBy('id')->get(['style_id', 'option_value_id', 'position']) as $row) {
            $positions[(int) $row->style_id][(int) $row->option_value_id] = (int) $row->position;
        }
        $chosen = [];
        foreach (array_chunk($variantIds, 1000) as $chunk) {
            foreach (DB::table('variant_option_values')->whereIn('variant_id', $chunk)->get(['variant_id', 'option_type_id', 'option_value_id']) as $row) {
                $chosen[(int) $row->variant_id][(int) $row->option_type_id] = (int) $row->option_value_id;
            }
        }
        $valueIds = array_values(array_unique(array_merge([], ...array_map('array_values', $chosen))));
        $values = $valueIds === [] ? collect() : DB::table('option_values')->whereIn('id', $valueIds)->get(['id', 'code', 'option_type_id'])->keyBy('id');
        $valueNames = $valueIds === [] ? collect() : DB::table('option_value_translations')->whereIn('option_value_id', $valueIds)->orderBy('id')->get(['option_value_id', 'locale', 'name'])->groupBy('option_value_id')->map($pick);
        $typeIds = $values->pluck('option_type_id')->unique()->values()->all();
        $types = $typeIds === [] ? collect() : DB::table('option_types')->whereIn('id', $typeIds)->pluck('code', 'id');
        $typeNames = $typeIds === [] ? collect() : DB::table('option_type_translations')->whereIn('option_type_id', $typeIds)->orderBy('id')->get(['option_type_id', 'locale', 'name'])->groupBy('option_type_id')->map($pick);

        $result = [];
        foreach ($variants as $row) {
            $id = (int) $row->id;
            $styleId = (int) $row->style_id;
            $options = [];
            $sort = [];
            foreach ($axes->get($styleId, []) as $typeId) {
                $valueId = $chosen[$id][$typeId] ?? null;
                $value = $valueId === null ? null : $values->get($valueId);
                if ($value === null) {
                    continue;
                }
                $typeCode = (string) $types->get($typeId);
                $options[] = [
                    'type' => $typeCode,
                    'name' => (string) ($typeNames->get($typeId) ?? $typeCode),
                    'code' => (string) $value->code,
                    'value' => (string) ($valueNames->get($valueId) ?? $value->code),
                ];
                $sort[] = sprintf('%05d', $positions[$styleId][$valueId] ?? 99999);
            }
            $result[$id] = [
                'data' => new VariantData(
                    id: $id,
                    brandId: $row->brand_id === null ? null : (int) $row->brand_id,
                    styleId: $styleId,
                    styleCode: (string) $row->style_code,
                    styleName: (string) $names->get($styleId),
                    sku: (string) $row->sku,
                    status: (string) $row->status,
                    options: $options,
                    tracksInventory: (bool) ($row->track_inventory ?? true),
                ),
                'sort' => sprintf('%010d-', $styleId).implode('-', $sort),
            ];
        }

        return $result;
    }

    public function search(string $text, int $limit = 2000): array
    {
        $text = trim($text);
        $like = '%'.addcslashes($text, '%_\\').'%';

        return Variant::query()
            ->when($text !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('sku', 'like', $like)
                ->orWhereHas('style', fn ($style) => $style->where('style_code', 'like', $like)
                    ->orWhereHas('translations', fn ($translation) => $translation->where('name', 'like', $like)))))
            ->limit(max(1, $limit))
            ->get()
            ->tap(fn ($variants) => VariantOptions::load($variants))
            ->sortBy(fn (Variant $variant): string => mb_strtolower((string) $variant->style->translate('name')).'|'.$variant->style->style_code.'|'.VariantOptions::sortKey($variant))
            ->map(fn (Variant $variant): VariantData => $this->toData($variant))
            ->values()
            ->all();
    }

    public function searchIndex(string $text = '', ?string $styleCode = null): array
    {
        $text = trim($text);
        $like = '%'.addcslashes($text, '%_\\').'%';
        $locale = StoreLocale::default();
        $scope = fn ($query) => $query
            ->when($styleCode !== null, fn ($query) => $query->where('s.style_code', $styleCode))
            ->when($styleCode === null && $text !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('v.sku', 'like', $like)
                ->orWhere('s.style_code', 'like', $like)
                ->orWhereExists(fn ($names) => $names->selectRaw('1')->from('style_translations as tn')->whereColumn('tn.style_id', 's.id')->where('tn.name', 'like', $like))));

        $rows = $scope(DB::table('variants as v')
            ->join('styles as s', 's.id', '=', 'v.style_id')
            ->leftJoin('style_translations as t', fn ($join) => $join->on('t.style_id', '=', 's.id')->where('t.locale', $locale)))
            ->get(['v.id', 'v.style_id', 's.style_code', 'v.status', 't.name']);

        // Giá trị của mọi trục (ADR-045) cùng vị trí trục và vị trí giá trị trong sản phẩm — thứ tự hiển thị.
        $values = $scope(DB::table('variant_option_values as vov')
            ->join('variants as v', 'v.id', '=', 'vov.variant_id')
            ->join('styles as s', 's.id', '=', 'v.style_id')
            ->join('option_types as ot', 'ot.id', '=', 'vov.option_type_id')
            ->join('option_values as ov', 'ov.id', '=', 'vov.option_value_id')
            ->leftJoin('style_options as so', fn ($join) => $join->on('so.style_id', '=', 'v.style_id')->on('so.option_type_id', '=', 'vov.option_type_id'))
            ->leftJoin('style_option_values as sov', fn ($join) => $join->on('sov.style_id', '=', 'v.style_id')->on('sov.option_value_id', '=', 'vov.option_value_id')))
            ->orderBy('so.position')
            ->get(['vov.variant_id', 'ot.code as type_code', 'ov.code as value_code', 'sov.position as value_position'])
            ->groupBy('variant_id');

        $index = [];
        foreach ($rows as $row) {
            $options = [];
            $sort = [];
            foreach ($values->get($row->id, collect()) as $value) {
                $options[(string) $value->type_code] = (string) $value->value_code;
                $sort[] = str_pad((string) (int) $value->value_position, 6, '0', STR_PAD_LEFT);
            }
            $index[] = [
                'row' => ['id' => (int) $row->id, 'style_id' => (int) $row->style_id, 'style_code' => (string) $row->style_code, 'options' => $options, 'status' => (string) $row->status],
                'key' => [mb_strtolower((string) ($row->name ?? $row->style_code)), (string) $row->style_code, implode('|', $sort), (int) $row->id],
            ];
        }
        usort($index, fn (array $a, array $b): int => $a['key'] <=> $b['key']);

        return array_column($index, 'row');
    }

    public function untrackedInventory(array $variantIds): array
    {
        if ($variantIds === []) {
            return [];
        }

        return Variant::query()->join('styles', 'styles.id', '=', 'variants.style_id')
            ->whereIn('variants.id', $variantIds)->where('styles.track_inventory', false)
            ->pluck('variants.id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    public function optionTypes(): array
    {
        return OptionType::query()->with('translations')->orderBy('position')->orderBy('code')->get()
            ->mapWithKeys(fn (OptionType $type): array => [$type->code => (string) ($type->translate('name') ?? $type->code)])->all();
    }

    private function toData(Variant $variant): VariantData
    {
        return new VariantData(
            id: $variant->id,
            brandId: $variant->style->brand_id,
            styleId: $variant->style_id,
            styleCode: $variant->style->style_code,
            styleName: (string) $variant->style->translate('name'),
            sku: $variant->sku,
            status: $variant->status->value,
            options: VariantOptions::present($variant),
            tracksInventory: (bool) ($variant->style->track_inventory ?? true),
        );
    }
}
