<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Contracts\Extensions;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\InventoryStrategy;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Data\SettingsScope;
use Modules\Tenancy\Contracts\Settings;

/**
 * ATS(kênh) = Σ ATS(location) của các location phục vụ kênh cho brand của variant,
 * sau đó áp InventoryStrategy nhưng không bao giờ vượt con số chuẩn.
 */
final class ChannelAvailability implements AvailabilityReader
{
    /** @deprecated dùng {@see InventoryStrategy::TAG} (public API). */
    public const TAG = InventoryStrategy::TAG;

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly Extensions $extensions,
        private readonly Settings $settings,
        private readonly CurrentContext $context,
        private readonly string $defaultCode,
    ) {}

    public function forChannel(array $variantIds, int $channelId): array
    {
        $variantIds = array_values(array_unique($variantIds));
        if ($variantIds === []) {
            return [];
        }

        $brands = array_map(fn ($variant): int => $variant->brandId, $this->variants->find($variantIds));

        $rows = DB::table('stock_levels')
            ->join('channel_locations', 'channel_locations.location_id', '=', 'stock_levels.location_id')
            ->join('locations', 'locations.id', '=', 'stock_levels.location_id')
            ->join('location_brands', 'location_brands.location_id', '=', 'locations.id')
            ->where('channel_locations.channel_id', $channelId)
            ->where('locations.status', 'active')
            ->where('locations.ships_online_orders', true)
            ->whereIn('stock_levels.variant_id', array_keys($brands))
            ->get(['stock_levels.variant_id', 'stock_levels.on_hand', 'stock_levels.reserved', 'stock_levels.safety_stock', 'location_brands.brand_id']);

        $standard = array_fill_keys($variantIds, 0);
        foreach ($rows as $row) {
            if (($brands[(int) $row->variant_id] ?? null) === (int) $row->brand_id) {
                $standard[(int) $row->variant_id] += max(0, (int) $row->on_hand - (int) $row->reserved - (int) $row->safety_stock);
            }
        }

        $adjusted = $this->strategy($channelId)->adjust($standard, $channelId);
        foreach ($standard as $variantId => $ats) {
            $standard[$variantId] = max(0, min($ats, (int) ($adjusted[$variantId] ?? $ats)));
        }

        return $standard;
    }

    private function strategy(int $channelId): InventoryStrategy
    {
        $scope = SettingsScope::fromContext($this->context->has() ? $this->context->scope() : null, $channelId);
        $code = (string) $this->settings->get('core', 'inventory.strategy', $scope, $this->defaultCode);
        $strategy = $this->extensions->select(InventoryStrategy::TAG, $code, $this->defaultCode);

        return $strategy instanceof InventoryStrategy ? $strategy : throw new InvalidArgumentException("InventoryStrategy [{$code}] chưa được đăng ký.");
    }
}
