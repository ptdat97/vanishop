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
            ->with(['style.translations', ...VariantOptions::relations()])
            ->whereHas('style', fn ($query) => $query->where('style_code', $styleCode))
            ->get()
            ->sortBy(fn (Variant $variant): string => VariantOptions::sortKey($variant))
            ->map(fn (Variant $variant): VariantData => $this->toData($variant))
            ->values()
            ->all();
    }

    public function ofStyles(array $styleIds): array
    {
        if ($styleIds === []) {
            return [];
        }

        return Variant::query()
            ->with(['style.translations', ...VariantOptions::relations()])
            ->whereIn('style_id', $styleIds)
            ->get()
            ->sortBy(fn (Variant $variant): string => VariantOptions::sortKey($variant))
            ->groupBy('style_id')
            ->map(fn ($variants): array => $variants->map(fn (Variant $variant): VariantData => $this->toData($variant))->values()->all())
            ->all();
    }

    public function find(array $variantIds): array
    {
        return Variant::query()
            ->with(['style.translations', ...VariantOptions::relations()])
            ->whereIn('id', $variantIds)
            ->get()
            ->mapWithKeys(fn (Variant $variant): array => [$variant->id => $this->toData($variant)])
            ->all();
    }

    public function findBySkus(array $skus): array
    {
        if ($skus === []) {
            return [];
        }

        return Variant::query()
            ->with(['style.translations', ...VariantOptions::relations()])
            ->whereIn('sku', array_values(array_unique($skus)))
            ->get()
            ->mapWithKeys(fn (Variant $variant): array => [$variant->sku => $this->toData($variant)])
            ->all();
    }

    public function search(string $text, int $limit = 2000): array
    {
        $text = trim($text);
        $like = '%'.addcslashes($text, '%_\\').'%';

        return Variant::query()
            ->with(['style.translations', ...VariantOptions::relations()])
            ->when($text !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('sku', 'like', $like)
                ->orWhereHas('style', fn ($style) => $style->where('style_code', 'like', $like)
                    ->orWhereHas('translations', fn ($translation) => $translation->where('name', 'like', $like)))))
            ->limit(max(1, $limit))
            ->get()
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
        );
    }
}
