<?php

declare(strict_types=1);

namespace Modules\Inventory\Tests\Feature;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Persistence\Models\Location;

require_once __DIR__.'/../../../Catalog/Tests/Feature/CatalogTestHelpers.php';
require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';

final class InventoryTestHelpers
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public static function location(array $attributes = []): Location
    {
        return T::seed(function () use ($attributes) {
            $location = Location::query()->create([
                'code' => $attributes['code'] ?? strtoupper(fake()->unique()->bothify('WH-??-##')),
                'name' => 'Kho '.fake()->city(),
                'type' => $attributes['type'] ?? 'warehouse',
                'priority' => $attributes['priority'] ?? 0,
                'stock_authority' => $attributes['stock_authority'] ?? 'vanishop',
                'ships_online_orders' => $attributes['ships_online_orders'] ?? true,
                'status' => $attributes['status'] ?? 'active',
            ]);

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
