<?php

declare(strict_types=1);

namespace Modules\Checkout\Tests\Feature;

use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Channel\Persistence\Models\Channel;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Promotion\Persistence\Models\Promotion;

require_once __DIR__.'/../../../Inventory/Tests/Feature/InventoryTestHelpers.php';

final class CheckoutTestHelpers
{
    /**
     * Brand + kênh + 2 variant (S giá 300.000, M giá 200.000) + kho có tồn.
     *
     * @return array{brand: Brand, channel: Channel, s: Variant, m: Variant, location: Location}
     */
    public static function store(string $slug = 'lumiere', int $stock = 10): array
    {
        $brand = Brand::factory()->create(['slug' => $slug, 'code' => strtoupper(substr($slug, 0, 2))]);
        $channel = Channel::factory()->forBrand($brand, 'vani.test', "/{$slug}")->create(['code' => "web-{$slug}"]);
        [$s, $m] = P::variants(T::product($brand->id, ['name' => 'Đầm lụa']), ['S', 'M']);
        P::priceList($brand->id, ['code' => 'base'], [$channel->id], [$s->id => [300_000], $m->id => [200_000]]);
        $location = I::location($brand, [$channel->id]);
        I::stock($location, $s->id, $stock);
        I::stock($location, $m->id, $stock);

        return ['brand' => $brand, 'channel' => $channel, 's' => $s, 'm' => $m, 'location' => $location];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function promotion(Brand $brand, array $attributes = [], array $vouchers = []): Promotion
    {
        return T::seed(function () use ($brand, $attributes, $vouchers) {
            $promotion = Promotion::query()->create([
                'brand_id' => $brand->id, 'name' => $attributes['name'] ?? 'Giảm 10%', 'status' => 'active',
                'priority' => $attributes['priority'] ?? 0, 'stacking' => $attributes['stacking'] ?? 'combinable',
                'requires_voucher' => $attributes['requires_voucher'] ?? $vouchers !== [],
                'action_type' => $attributes['action_type'] ?? 'percent_off', 'action_config' => $attributes['action_config'] ?? ['basis_points' => 1000],
                'usage_limit' => $attributes['usage_limit'] ?? null, 'budget_amount' => $attributes['budget_amount'] ?? null,
                'starts_at' => $attributes['starts_at'] ?? null, 'ends_at' => $attributes['ends_at'] ?? null,
            ]);
            foreach ($vouchers as $code => $limit) {
                $promotion->vouchers()->create(['code' => $code, 'usage_limit' => $limit, 'status' => 'active']);
            }

            return $promotion;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public static function orderPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'contact' => ['full_name' => 'Nguyễn Thị Lan', 'phone' => '0912 345 678', 'email' => 'Lan@Example.com'],
            'shipping_address' => ['province_code' => '79', 'province_name' => 'TP. Hồ Chí Minh', 'ward_code' => '26734', 'ward_name' => 'Phường Bến Thành', 'street_line' => '12 Lê Lợi'],
            'shipping_method' => 'standard',
            'payment_method' => 'cod',
            'voucher_codes' => [],
        ], $overrides);
    }
}
