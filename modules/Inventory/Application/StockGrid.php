<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Lưới tồn kho Admin: mọi variant (tìm theo tên/mã sản phẩm/SKU, lọc màu, size, hết hàng / sắp hết theo số bán được
 * online — Σ max(0, tồn − giữ − an toàn) của kho đang giao online, như storefront) × kho. Phân trang theo **sản phẩm**
 * để các variant của một sản phẩm luôn cùng trang (chọn cả sản phẩm, sửa hàng loạt).
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
     * @param  array{q?: string, style?: string, filter?: string, color?: string, size?: string}  $criteria
     * @return array{products: list<array<string, mixed>>, total_products: int, total_variants: int, locations: list<Location>, colors: list<string>, sizes: list<string>}
     */
    public function page(array $criteria, int $page, int $perPage = 20): array
    {
        $matching = $this->matching($criteria);
        $products = [];
        foreach ($matching['rows'] as $row) {
            $products[$row['style_code']] ??= ['style_code' => $row['style_code'], 'style_name' => $row['style_name'], 'sellable' => 0, 'variants' => []];
            $products[$row['style_code']]['sellable'] += $row['sellable'];
            $products[$row['style_code']]['variants'][] = $row;
        }
        $products = array_values($products);

        return [
            'products' => array_slice($products, max(0, $page - 1) * $perPage, $perPage),
            'total_products' => count($products), 'total_variants' => count($matching['rows']),
            'locations' => $matching['locations'], 'colors' => $matching['colors'], 'sizes' => $matching['sizes'],
        ];
    }

    /**
     * Id mọi variant khớp bộ lọc (chọn "tất cả kết quả" để sửa hàng loạt).
     *
     * @param  array{q?: string, style?: string, filter?: string, color?: string, size?: string}  $criteria
     * @return list<int>
     */
    public function variantIds(array $criteria): array
    {
        return array_map(fn (array $row): int => $row['id'], $this->matching($criteria)['rows']);
    }

    /**
     * Tồn bán được (tổng ở kho giao online) theo sản phẩm — cột danh sách sản phẩm Admin (0.3.103).
     *
     * @param  array<int, list<int>>  $variantIdsByStyle  style id => variant id
     * @return array<int, int> style id => số bán được
     */
    public function sellableByStyle(array $variantIdsByStyle): array
    {
        $online = array_values(array_filter($this->queries->locations(), fn (Location $location): bool => $location->status === 'active' && $location->ships_online_orders));
        $all = array_merge([], ...array_values($variantIdsByStyle));
        $levels = $all === [] ? [] : $this->queries->levels($all);
        $result = [];
        foreach ($variantIdsByStyle as $styleId => $variantIds) {
            $result[$styleId] = 0;
            foreach ($variantIds as $variantId) {
                foreach ($online as $location) {
                    $result[$styleId] += max(0, $levels[$location->id.':'.$variantId]['available'] ?? 0);
                }
            }
        }

        return $result;
    }

    /**
     * Số biến thể đang bán (status active) hết hàng / sắp hết ở kho giao online — ô "Việc cần làm" (0.3.101).
     *
     * @return array{out: int, low: int}
     */
    public function attentionCounts(): array
    {
        $threshold = (int) config('vanishop.inventory.low_stock_threshold', 3);
        $active = array_filter($this->matching([])['rows'], fn (array $row): bool => $row['status'] === 'active');

        return [
            'out' => count(array_filter($active, fn (array $row): bool => $row['sellable'] === 0)),
            'low' => count(array_filter($active, fn (array $row): bool => $row['sellable'] > 0 && $row['sellable'] <= $threshold)),
        ];
    }

    /**
     * @param  array{q?: string, style?: string, filter?: string, color?: string, size?: string}  $criteria
     * @return array{rows: list<array<string, mixed>>, locations: list<Location>, colors: list<string>, sizes: list<string>}
     */
    private function matching(array $criteria): array
    {
        $style = trim((string) ($criteria['style'] ?? ''));
        $filter = in_array($criteria['filter'] ?? 'all', self::FILTERS, true) ? (string) ($criteria['filter'] ?? 'all') : 'all';
        $color = trim((string) ($criteria['color'] ?? ''));
        $size = trim((string) ($criteria['size'] ?? ''));

        $locations = $this->queries->locations();
        $variants = $style !== '' ? $this->variants->ofStyleCode($style) : $this->variants->search((string) ($criteria['q'] ?? ''), self::MAX_VARIANTS);
        // Lựa chọn màu/size lấy từ kết quả tìm (trước khi lọc màu/size) để chỉ hiện giá trị có thật.
        $colors = array_values(array_unique(array_map(fn (VariantData $variant): string => $variant->colorCode, $variants)));
        $sizes = array_values(array_unique(array_map(fn (VariantData $variant): string => $variant->sizeCode, $variants)));
        $variants = array_values(array_filter($variants, fn (VariantData $variant): bool => ($color === '' || $variant->colorCode === $color) && ($size === '' || $variant->sizeCode === $size)));

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

        return ['rows' => $rows, 'locations' => $locations, 'colors' => $colors, 'sizes' => $sizes];
    }
}
