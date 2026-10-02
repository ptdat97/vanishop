<?php

declare(strict_types=1);

namespace Modules\Pricing\Tests\Feature;

use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\Size;
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
            $color = Color::factory()->create();
            $styleColor = $style->colors()->create(['color_id' => $color->id]);
            $variants = [];
            foreach ($sizes as $index => $code) {
                $size = Size::factory()->create(['code' => $code.fake()->unique()->numberBetween(1, 99999), 'sort_order' => $index]);
                $variants[] = Variant::query()->create([
                    'style_id' => $style->id, 'style_color_id' => $styleColor->id,
                    'size_id' => $size->id, 'sku' => $style->style_code.'-'.$size->code, 'status' => 'active',
                ]);
            }

            return $variants;
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
