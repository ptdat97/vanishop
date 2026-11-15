<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Contracts\Extensions;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Inventory\Contracts\InventoryStrategy;
use Modules\Tenancy\Contracts\Settings;

/**
 * ATS = Σ ATS(location) của các location đang hoạt động và giao đơn online, sau đó áp InventoryStrategy
 * nhưng không bao giờ vượt con số chuẩn. SKU của sản phẩm không theo dõi tồn → `UNLIMITED`. SKU có hạn mức đặt trước
 * (`BackorderPolicy`, ADR-047): ATS của kho bù tồn = max(0, available + hạn mức).
 */
final class StockAvailability implements AvailabilityReader
{
    public function __construct(
        private readonly Extensions $extensions,
        private readonly Settings $settings,
        private readonly string $defaultCode,
        private readonly VariantDirectory $variants,
        private readonly OnlineLocations $locations,
        private readonly BackorderService $backorders,
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
            ->get(['stock_levels.variant_id', 'stock_levels.location_id', 'stock_levels.on_hand', 'stock_levels.reserved', 'stock_levels.safety_stock']);

        $topUpLocation = $this->locations->topUpLocation();
        $allowances = $topUpLocation === null ? [] : array_map(fn ($allowance): int => $allowance->limit, $this->backorders->allowances($variantIds));
        $standard = array_fill_keys($variantIds, 0);
        $backorderCounted = [];
        foreach ($rows as $row) {
            $variantId = (int) $row->variant_id;
            $backorder = 0;
            if ((int) $row->location_id === $topUpLocation && isset($allowances[$variantId])) {
                $backorder = $allowances[$variantId];
                $backorderCounted[$variantId] = true;
            }
            $standard[$variantId] += max(0, (int) $row->on_hand - (int) $row->reserved - (int) $row->safety_stock + $backorder);
        }
        // Chưa có dòng tồn ở kho bù tồn: hạn mức nguyên vẹn (dòng tồn tạo khi giữ hàng).
        foreach ($allowances as $variantId => $limit) {
            if (! isset($backorderCounted[$variantId])) {
                $standard[$variantId] += $limit;
            }
        }

        $adjusted = $this->strategy()->adjust($standard);
        foreach ($standard as $variantId => $ats) {
            $standard[$variantId] = max(0, min($ats, (int) ($adjusted[$variantId] ?? $ats)));
        }
        // Không theo dõi tồn: luôn bán được khi có kho online do VaniShop quản lý tồn để bù (ReservationService); không có thì
        // tính như sản phẩm thường để giỏ/checkout không hứa bán được rồi giữ hàng thất bại.
        if ($topUpLocation !== null) {
            foreach ($this->variants->untrackedInventory($variantIds) as $variantId) {
                $standard[$variantId] = self::UNLIMITED;
            }
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
