<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Persistence\Models\Location;

require_once __DIR__.'/../../../Catalog/Tests/Feature/CatalogTestHelpers.php';
require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';

final class InventoryTestHelpers
{
    /**
     * @param  list<int>  $channelIds
     * @param  array<string, mixed>  $attributes
     */
    public static function location(Brand $brand, array $channelIds, array $attributes = []): Location
    {
        return T::seed(function () use ($brand, $channelIds, $attributes) {
            $location = Location::query()->create([
                'legal_entity_id' => $brand->legal_entity_id,
                'code' => $attributes['code'] ?? strtoupper(fake()->unique()->bothify('WH-??-##')),
                'name' => 'Kho '.fake()->city(),
                'type' => $attributes['type'] ?? 'warehouse',
                'priority' => $attributes['priority'] ?? 0,
                'stock_authority' => $attributes['stock_authority'] ?? 'vanishop',
                'ships_online_orders' => $attributes['ships_online_orders'] ?? true,
                'status' => $attributes['status'] ?? 'active',
            ]);
            DB::table('location_brands')->insert(['location_id' => $location->id, 'brand_id' => $brand->id]);
            foreach ($channelIds as $channelId) {
                DB::table('channel_locations')->insert(['channel_id' => $channelId, 'location_id' => $location->id]);
            }

            return $location;
        });
    }

    public static function stock(Location $location, int $variantId, int $onHand, int $safetyStock = 0): void
    {
        DB::table('stock_levels')->updateOrInsert(
            ['location_id' => $location->id, 'variant_id' => $variantId],
            ['on_hand' => $onHand, 'reserved' => 0, 'safety_stock' => $safetyStock, 'created_at' => now(), 'updated_at' => now()],
        );
    }
}
