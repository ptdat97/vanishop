<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\StyleOption;
use Modules\Catalog\Persistence\Models\StyleOptionValue;
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
            'optionValues.type.translations', 'optionValues.translations', 'style.options.type', 'style.optionValues',
        ]);
    }

    /**
     * Giá trị của SKU theo thứ tự trục của sản phẩm.
     *
     * @return list<OptionValue>
     */
    public static function ordered(Variant $variant): array
    {
        $byType = $variant->optionValues->keyBy(fn (OptionValue $value): int => $value->option_type_id);

        return array_values(array_filter($variant->style->options->map(fn (StyleOption $axis): ?OptionValue => $byType->get($axis->option_type_id))->all()));
    }

    /** Khoá sắp xếp SKU trong sản phẩm: vị trí giá trị (theo thứ tự riêng của sản phẩm) của từng trục. */
    public static function sortKey(Variant $variant): string
    {
        $positions = $variant->style->optionValues->mapWithKeys(fn (StyleOptionValue $row): array => [$row->option_value_id => $row->position]);

        return sprintf('%010d-', $variant->style_id).implode('-', array_map(fn (OptionValue $value): string => sprintf('%05d', $positions[$value->id] ?? 99999), self::ordered($variant)));
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
    public static function axisTypeIds(Style $style): array
    {
        return $style->options->map(fn (StyleOption $axis): int => $axis->option_type_id)->values()->all();
    }
}
