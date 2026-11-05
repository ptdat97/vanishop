<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Lưới tồn kho Admin: mọi variant (tìm theo tên/mã sản phẩm/SKU) × kho, lọc hết hàng / sắp hết theo số có thể bán online
 * (Σ max(0, tồn − giữ − an toàn) của kho đang giao online — như storefront).
 */
final class StockGrid
{
    public const FILTERS = ['all', 'out', 'low'];

    public const MAX_VARIANTS = 5000;

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly StockQueries $queries,
    ) {}

    /**
     * @param  'all'|'out'|'low'  $filter
     * @return array{rows: list<array<string, mixed>>, total: int, locations: list<Location>}
     */
    public function page(string $text, ?string $styleCode, string $filter, int $page, int $perPage = 50): array
    {
        $locations = $this->queries->locations();
        $variants = $styleCode !== null && $styleCode !== '' ? $this->variants->ofStyleCode($styleCode) : $this->variants->search($text, self::MAX_VARIANTS);
        $levels = $variants === [] ? [] : $this->queries->levels(array_map(fn (VariantData $variant): int => $variant->id, $variants));
        $online = array_values(array_filter($locations, fn (Location $location): bool => $location->status === 'active' && $location->ships_online_orders));
        $threshold = (int) config('vanishop.inventory.low_stock_threshold', 3);

        $rows = [];
        foreach ($variants as $variant) {
            $sellable = 0;
            foreach ($online as $location) {
                $sellable += max(0, $levels[$location->id.':'.$variant->id]['available'] ?? 0);
            }
            if (($filter === 'out' && $sellable > 0) || ($filter === 'low' && ($sellable === 0 || $sellable > $threshold))) {
                continue;
            }
            $rows[] = [
                'id' => $variant->id, 'sku' => $variant->sku, 'style_code' => $variant->styleCode, 'style_name' => $variant->styleName,
                'color_code' => $variant->colorCode, 'size_code' => $variant->sizeCode, 'status' => $variant->status, 'sellable' => $sellable,
                'levels' => array_map(fn (Location $location): array => $levels[$location->id.':'.$variant->id] ?? ['on_hand' => 0, 'reserved' => 0, 'safety_stock' => 0, 'available' => 0], $locations),
            ];
        }

        return ['rows' => array_slice($rows, max(0, $page - 1) * $perPage, $perPage), 'total' => count($rows), 'locations' => $locations];
    }
}
