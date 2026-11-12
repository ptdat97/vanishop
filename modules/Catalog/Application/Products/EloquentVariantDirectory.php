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
        $types = OptionType::query()->whereIn('code', [OptionType::COLOR, OptionType::SIZE])->pluck('id', 'code');
        // Giá trị màu/size của SKU (nếu sản phẩm có trục đó) và vị trí của giá trị trong sản phẩm — thứ tự hiển thị.
        $axis = function ($query, string $alias, ?int $typeId): void {
            $query->leftJoin("variant_option_values as {$alias}v", fn ($join) => $join->on("{$alias}v.variant_id", '=', 'v.id')->where("{$alias}v.option_type_id", '=', $typeId ?? 0))
                ->leftJoin("option_values as {$alias}", "{$alias}.id", '=', "{$alias}v.option_value_id")
                ->leftJoin("style_option_values as {$alias}p", fn ($join) => $join->on("{$alias}p.style_id", '=', 'v.style_id')->on("{$alias}p.option_value_id", '=', "{$alias}v.option_value_id"));
        };

        $query = DB::table('variants as v')
            ->join('styles as s', 's.id', '=', 'v.style_id')
            ->leftJoin('style_translations as t', fn ($join) => $join->on('t.style_id', '=', 's.id')->where('t.locale', $locale));
        $axis($query, 'c', $types[OptionType::COLOR] ?? null);
        $axis($query, 'z', $types[OptionType::SIZE] ?? null);

        return $query
            ->when($styleCode !== null, fn ($query) => $query->where('s.style_code', $styleCode))
            ->when($styleCode === null && $text !== '', fn ($query) => $query->where(fn ($inner) => $inner
                ->where('v.sku', 'like', $like)
                ->orWhere('s.style_code', 'like', $like)
                ->orWhereExists(fn ($names) => $names->selectRaw('1')->from('style_translations as tn')->whereColumn('tn.style_id', 's.id')->where('tn.name', 'like', $like))))
            ->orderByRaw('lower(coalesce(t.name, s.style_code))')->orderBy('s.style_code')->orderBy('cp.position')->orderBy('zp.position')->orderBy('v.id')
            ->get(['v.id', 'v.style_id', 's.style_code', 'c.code as color_code', 'z.code as size_code', 'v.status'])
            ->map(fn (object $row): array => [
                'id' => (int) $row->id, 'style_id' => (int) $row->style_id, 'style_code' => (string) $row->style_code,
                'color_code' => (string) $row->color_code, 'size_code' => (string) $row->size_code, 'status' => (string) $row->status,
            ])->all();
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
            colorCode: VariantOptions::code($variant, OptionType::COLOR),
            sizeCode: VariantOptions::code($variant, OptionType::SIZE),
            status: $variant->status->value,
            options: VariantOptions::present($variant),
        );
    }
}
