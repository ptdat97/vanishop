<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Extension\Contracts\Extensions;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\InventoryStrategy;
use Modules\Tenancy\Contracts\Settings;

/**
 * ATS = Σ ATS(location) của các location đang hoạt động và giao đơn online, sau đó áp InventoryStrategy
 * nhưng không bao giờ vượt con số chuẩn.
 */
final class StockAvailability implements AvailabilityReader
{
    public function __construct(
        private readonly Extensions $extensions,
        private readonly Settings $settings,
        private readonly string $defaultCode,
    ) {}

    public function forVariants(array $variantIds): array
    {
        $variantIds = array_values(array_unique($variantIds));
        if ($variantIds === []) {
            return [];
        }

        $rows = DB::table('stock_levels')
            ->join('locations', 'locations.id', '=', 'stock_levels.location_id')
            ->where('locations.status', 'active')
            ->where('locations.ships_online_orders', true)
            ->whereIn('stock_levels.variant_id', $variantIds)
            ->get(['stock_levels.variant_id', 'stock_levels.on_hand', 'stock_levels.reserved', 'stock_levels.safety_stock']);

        $standard = array_fill_keys($variantIds, 0);
        foreach ($rows as $row) {
            $standard[(int) $row->variant_id] += max(0, (int) $row->on_hand - (int) $row->reserved - (int) $row->safety_stock);
        }

        $adjusted = $this->strategy()->adjust($standard);
        foreach ($standard as $variantId => $ats) {
            $standard[$variantId] = max(0, min($ats, (int) ($adjusted[$variantId] ?? $ats)));
        }

        return $standard;
    }

    private function strategy(): InventoryStrategy
    {
        $code = (string) $this->settings->get('core', 'inventory.strategy', $this->defaultCode);
        $strategy = $this->extensions->select(InventoryStrategy::TAG, $code, $this->defaultCode);

        return $strategy instanceof InventoryStrategy ? $strategy : throw new InvalidArgumentException("InventoryStrategy [{$code}] chưa được đăng ký.");
    }
}
