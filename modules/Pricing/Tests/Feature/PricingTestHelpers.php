<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Feature;

use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Persistence\Models\PriceList;

require_once __DIR__.'/../../../Catalog/Tests/Feature/CatalogTestHelpers.php';

final class PricingTestHelpers
{
    /**
     * Style có n variant (1 màu × n size).
     *
     * @param  list<string>  $sizes
     * @return list<Variant>
     */
    public static function variants(Style $style, array $sizes = ['S', 'M']): array
    {
        return T::seed(function () use ($style, $sizes) {
            // ADR-045: trục Màu × Size; mỗi lần gọi thêm một màu mới cho sản phẩm và các size (mã duy nhất).
            $color = OptionValue::factory()->color()->create();
            $sizeValues = [];
            foreach ($sizes as $index => $code) {
                $sizeValues[] = OptionValue::factory()->size()->create(['code' => $code.fake()->unique()->numberBetween(1, 99999), 'position' => $index]);
            }
            T::optionValues($style, $color, ...$sizeValues);

            return array_map(fn (OptionValue $size): Variant => app(ProductOptions::class)->createVariant($style, [$color->id, $size->id], ['sku' => $style->style_code.'-'.$size->code]), $sizeValues);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array{0: int, 1?: int|null}>  $prices  variant id => [amount, compare_at]
     */
    public static function priceList(array $attributes, array $prices): PriceList
    {
        return T::seed(function () use ($attributes, $prices) {
            $list = PriceList::query()->create([
                'code' => $attributes['code'] ?? fake()->unique()->lexify('list-????'), 'name' => 'Bảng giá',
                'currency_code' => 'VND', 'type' => $attributes['type'] ?? 'base', 'priority' => $attributes['priority'] ?? 0,
                'starts_at' => $attributes['starts_at'] ?? null, 'ends_at' => $attributes['ends_at'] ?? null, 'status' => $attributes['status'] ?? 'active',
            ]);
            foreach ($prices as $variantId => $price) {
                $list->prices()->create(['variant_id' => $variantId, 'amount' => $price[0], 'compare_at_amount' => $price[1] ?? null]);
            }

            return $list;
        });
    }
}
