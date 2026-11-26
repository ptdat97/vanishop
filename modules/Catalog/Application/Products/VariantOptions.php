<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Catalog\Persistence\Models\ProductOption;
use Modules\Catalog\Persistence\Models\ProductOptionValue;
use Modules\Catalog\Persistence\Models\Variant;

/**
 * Đọc tổ hợp tuỳ chọn của SKU (ADR-045): thứ tự trục và giá trị theo sản phẩm, danh sách `options` cho hợp đồng dữ liệu.
 */
final class VariantOptions
{
    /**
     * Quan hệ cần nạp trên Variant để dùng các hàm dưới đây.
     *
     * @return list<string>
     */
    public static function relations(string $prefix = ''): array
    {
        return array_map(fn (string $relation): string => $prefix.$relation, [
            'optionValues.type.translations', 'optionValues.translations', 'product.options.type', 'product.optionValues',
        ]);
    }

    /**
     * Nạp sẵn mọi thứ các hàm dưới đây cần cho một lô SKU, **dùng chung** model giữa các SKU: product (kèm bản dịch, trục,
     * vị trí giá trị) mỗi sản phẩm một lần, giá trị tuỳ chọn (kèm loại, bản dịch) mỗi giá trị một lần. Thay cho
     * `with(relations())` ở đường đọc nhiều SKU: eager load quan hệ nhiều-nhiều dựng một bản sao giá trị (và loại, bản dịch)
     * cho từng SKU — 3.000 SKU × 2 trục ≈ 30.000 model, phần lớn thời gian của danh sách sản phẩm/tồn/bảng giá Admin.
     *
     * @param  EloquentCollection<int, Variant>  $variants
     */
    public static function load(EloquentCollection $variants): void
    {
        if ($variants->isEmpty()) {
            return;
        }

        $variants->load(['product.translations', 'product.options.type', 'product.optionValues']);
        $rows = DB::table('variant_option_values')->whereIn('variant_id', $variants->modelKeys())->get(['variant_id', 'option_value_id']);
        $values = OptionValue::query()->with(['type.translations', 'translations'])
            ->whereKey($rows->pluck('option_value_id')->unique()->values()->all())->get()->keyBy('id');
        $byVariant = $rows->groupBy('variant_id');

        foreach ($variants as $variant) {
            $variant->setRelation('optionValues', new EloquentCollection(
                $byVariant->get($variant->id, collect())->map(fn (object $row): ?OptionValue => $values->get($row->option_value_id))->filter()->values()->all(),
            ));
        }
    }

    /**
     * Giá trị của SKU theo thứ tự trục của sản phẩm.
     *
     * @return list<OptionValue>
     */
    public static function ordered(Variant $variant): array
    {
        $byType = $variant->optionValues->keyBy(fn (OptionValue $value): int => $value->option_type_id);

        return array_values(array_filter($variant->product->options->map(fn (ProductOption $axis): ?OptionValue => $byType->get($axis->option_type_id))->all()));
    }

    /** Khoá sắp xếp SKU trong sản phẩm: vị trí giá trị (theo thứ tự riêng của sản phẩm) của từng trục. */
    public static function sortKey(Variant $variant): string
    {
        $positions = $variant->product->optionValues->mapWithKeys(fn (ProductOptionValue $row): array => [$row->option_value_id => $row->position]);

        return sprintf('%010d-', $variant->product_id).implode('-', array_map(fn (OptionValue $value): string => sprintf('%05d', $positions[$value->id] ?? 99999), self::ordered($variant)));
    }

    /**
     * Danh sách tuỳ chọn của SKU cho hợp đồng dữ liệu: loại (mã, tên) và giá trị (mã, tên) theo thứ tự trục.
     *
     * @return list<array{type: string, name: string, code: string, value: string}>
     */
    public static function present(Variant $variant, ?string $locale = null): array
    {
        return array_map(fn (OptionValue $value): array => [
            'type' => $value->type->code,
            'name' => (string) ($value->type->translate('name', $locale) ?? $value->type->code),
            'code' => $value->code,
            'value' => $value->label($locale),
        ], self::ordered($variant));
    }

    /** Mã giá trị của SKU cho một loại (vd. `color`, `size`); '' nếu sản phẩm không có trục đó. */
    public static function code(Variant $variant, string $typeCode): string
    {
        return $variant->valueOf($typeCode)->code ?? '';
    }

    /**
     * Trục và giá trị của một sản phẩm cho màn hình/hợp đồng (đã nạp `options.type`, `optionValues.value`).
     *
     * @return list<int> id loại tuỳ chọn theo thứ tự trục
     */
    public static function axisTypeIds(Product $product): array
    {
        return $product->options->map(fn (ProductOption $axis): int => $axis->option_type_id)->values()->all();
    }
}
