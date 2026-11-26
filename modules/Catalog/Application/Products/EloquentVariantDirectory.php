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
    public function ofProductCode(string $productCode): array
    {
        return Variant::query()
            ->whereHas('product', fn ($query) => $query->where('product_code', $productCode))
            ->get()
            ->tap(fn ($variants) => VariantOptions::load($variants))
            ->sortBy(fn (Variant $variant): string => VariantOptions::sortKey($variant))
            ->map(fn (Variant $variant): VariantData => $this->toData($variant))
            ->values()
            ->all();
    }

    public function ofProducts(array $productIds): array
    {
        $sorted = $this->read('product_id', $productIds);
        uasort($sorted, fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);
        $result = [];
        foreach ($sorted as $row) {
            $result[$row['data']->productId][] = $row['data'];
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
     * @param  'id'|'product_id'|'sku'  $column
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
            $variants = $variants->concat(DB::table('variants as v')->join('products as s', 's.id', '=', 'v.product_id')->whereIn("v.{$column}", $chunk)
                ->get(['v.id', 'v.product_id', 'v.sku', 'v.status', 's.brand_id', 's.product_code', 's.track_inventory']));
        }
        if ($variants->isEmpty()) {
            return [];
        }

        $variantIds = $variants->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $productIds = $variants->pluck('product_id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
        $locale = app()->getLocale();
        $fallback = StoreLocale::default();
        $pick = function ($translations) use ($locale, $fallback): ?string {
            $translation = $translations->firstWhere('locale', $locale) ?? $translations->firstWhere('locale', $fallback) ?? $translations->first();

            return $translation?->name === null ? null : (string) $translation->name;
        };

        $names = DB::table('product_translations')->whereIn('product_id', $productIds)->orderBy('id')->get(['product_id', 'locale', 'name'])->groupBy('product_id')->map($pick);
        $axes = DB::table('product_options')->whereIn('product_id', $productIds)->orderBy('position')->orderBy('id')->get(['product_id', 'option_type_id'])
            ->groupBy('product_id')->map(fn ($rows): array => $rows->pluck('option_type_id')->map(fn ($id): int => (int) $id)->all());
        $positions = [];
        foreach (DB::table('product_option_values as pov')->join('product_options as po', 'po.id', '=', 'pov.product_option_id')->whereIn('po.product_id', $productIds)
            ->orderBy('pov.position')->orderBy('pov.id')->get(['po.product_id', 'pov.option_value_id', 'pov.position']) as $row) {
            $positions[(int) $row->product_id][(int) $row->option_value_id] = (int) $row->position;
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
            $productId = (int) $row->product_id;
            $options = [];
            $sort = [];
            foreach ($axes->get($productId, []) as $typeId) {
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
                $sort[] = sprintf('%05d', $positions[$productId][$valueId] ?? 99999);
            }
            $result[$id] = [
                'data' => new VariantData(
                    id: $id,
                    brandId: $row->brand_id === null ? null : (int) $row->brand_id,
                    productId: $productId,
                    productCode: (string) $row->product_code,
                    productName: (string) $names->get($productId),
                    sku: (string) $row->sku,
                    status: (string) $row->status,
                    options: $options,
                    tracksInventory: (bool) ($row->track_inventory ?? true),
                ),
                'sort' => sprintf('%010d-', $productId).implode('-', $sort),
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
                ->orWhereHas('product', fn ($product) => $product->where('product_code', 'like', $like)
                    ->orWhereHas('translations', fn ($translation) => $translation->where('name', 'like', $like)))))
            ->limit(max(1, $limit))
            ->get()
            ->tap(fn ($variants) => VariantOptions::load($variants))
            ->sortBy(fn (Variant $variant): string => mb_strtolower((string) $variant->product->translate('name')).'|'.$variant->product->product_code.'|'.VariantOptions::sortKey($variant))
            ->map(fn (Variant $variant): VariantData => $this->toData($variant))
            ->values()
            ->all();
    }

    public function untrackedInventory(array $variantIds): array
    {
        if ($variantIds === []) {
            return [];
        }

        return Variant::query()->join('products', 'products.id', '=', 'variants.product_id')
            ->whereIn('variants.id', $variantIds)->where('products.track_inventory', false)
            ->pluck('variants.id')->map(fn (mixed $id): int => (int) $id)->all();
    }

    public function productIdsOf(array $variantIds): array
    {
        if ($variantIds === []) {
            return [];
        }

        return Variant::query()->whereIn('id', $variantIds)->pluck('product_id', 'id')
            ->mapWithKeys(fn (mixed $productId, mixed $id): array => [(int) $id => (int) $productId])->all();
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
            brandId: $variant->product->brand_id,
            productId: $variant->product_id,
            productCode: $variant->product->product_code,
            productName: (string) $variant->product->translate('name'),
            sku: $variant->sku,
            status: $variant->status->value,
            options: VariantOptions::present($variant),
            tracksInventory: (bool) ($variant->product->track_inventory ?? true),
        );
    }
}
