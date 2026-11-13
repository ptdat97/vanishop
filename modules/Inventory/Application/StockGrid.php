<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Lưới tồn kho Admin: mọi variant (tìm theo tên/mã sản phẩm/SKU, lọc theo giá trị tuỳ chọn — màu, size…, hết hàng / sắp hết theo số bán được
 * online — Σ max(0, tồn − giữ − an toàn) của kho đang giao online, như storefront) × kho. Phân trang theo **sản phẩm**
 * để các variant của một sản phẩm luôn cùng trang (chọn cả sản phẩm, sửa hàng loạt).
 */
final class StockGrid
{
    public const FILTERS = ['all', 'out', 'low'];

    /** Sửa hàng loạt chạy đồng bộ trong request: chọn "mọi kết quả lọc" vượt mức này → báo thu hẹp bộ lọc (không cắt âm thầm). */
    public const MAX_BULK_VARIANTS = 5000;

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly StockQueries $queries,
        private readonly LowStockThreshold $lowStock,
    ) {}

    /**
     * @param  array{q?: string, style?: string, filter?: string, options?: array<string, string>}  $criteria
     * @return array{products: list<array<string, mixed>>, total_products: int, total_variants: int, locations: list<Location>, option_filters: list<array{code: string, name: string, values: list<string>}>}
     */
    public function page(array $criteria, int $page, int $perPage = 20): array
    {
        $matching = $this->matching($criteria);
        $byStyle = [];
        foreach ($matching['rows'] as $row) {
            $byStyle[$row['style_id']][] = $row;
        }
        $pageRows = array_merge([], ...array_slice(array_values($byStyle), max(0, $page - 1) * $perPage, $perPage));

        // Chỉ phần đang hiển thị mới đọc đủ dữ liệu (tên, SKU) và số liệu từng kho.
        $ids = array_map(fn (array $row): int => $row['id'], $pageRows);
        $variants = $ids === [] ? [] : $this->variants->find($ids);
        $levels = $ids === [] ? [] : $this->queries->levels($ids);
        $products = [];
        foreach ($pageRows as $row) {
            $variant = $variants[$row['id']] ?? null;
            if ($variant === null) {
                continue;
            }
            $products[$row['style_code']] ??= ['style_code' => $row['style_code'], 'style_name' => $variant->styleName, 'tracked' => $variant->tracksInventory, 'sellable' => 0, 'variants' => []];
            $products[$row['style_code']]['sellable'] += $row['sellable'];
            $products[$row['style_code']]['variants'][] = [
                'id' => $row['id'], 'sku' => $variant->sku, 'style_code' => $row['style_code'], 'style_name' => $variant->styleName,
                'options' => $variant->options, 'options_label' => VariantOptionsText::label($variant->options), 'status' => $row['status'], 'sellable' => $row['sellable'], 'tracked' => $row['tracked'],
                'levels' => array_map(fn (Location $location): array => $levels[$location->id.':'.$row['id']] ?? ['on_hand' => 0, 'reserved' => 0, 'safety_stock' => 0, 'available' => 0], $matching['locations']),
            ];
        }

        return [
            'products' => array_values($products),
            'total_products' => count($byStyle), 'total_variants' => count($matching['rows']),
            'locations' => $matching['locations'], 'option_filters' => $matching['option_filters'],
        ];
    }

    /**
     * Id mọi variant khớp bộ lọc (chọn "tất cả kết quả" để sửa hàng loạt).
     *
     * @param  array{q?: string, style?: string, filter?: string, options?: array<string, string>}  $criteria
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
     * Số biến thể đang bán (status active) hết hàng / sắp hết ở kho giao online — ô "Việc cần làm" (0.3.101). Sản phẩm không
     * theo dõi tồn luôn bán được nên không tính.
     *
     * @return array{out: int, low: int}
     */
    public function attentionCounts(): array
    {
        $threshold = $this->lowStock->value();
        $active = array_filter($this->matching([])['rows'], fn (array $row): bool => $row['status'] === 'active' && $row['tracked']);

        return [
            'out' => count(array_filter($active, fn (array $row): bool => $row['sellable'] === 0)),
            'low' => count(array_filter($active, fn (array $row): bool => $row['sellable'] > 0 && $row['sellable'] <= $threshold)),
        ];
    }

    /**
     * Mọi variant khớp (không giới hạn): khoá + số bán được, tính bằng chỉ mục của Catalog và một truy vấn gộp tồn.
     *
     * @param  array{q?: string, style?: string, filter?: string, options?: array<string, string>}  $criteria
     * @return array{rows: list<array{id: int, style_id: int, style_code: string, options: array<string, string>, status: string, sellable: int, tracked: bool}>, locations: list<Location>, option_filters: list<array{code: string, name: string, values: list<string>}>}
     */
    private function matching(array $criteria): array
    {
        $style = trim((string) ($criteria['style'] ?? ''));
        $filter = in_array($criteria['filter'] ?? 'all', self::FILTERS, true) ? (string) ($criteria['filter'] ?? 'all') : 'all';
        $wanted = array_filter(array_map(fn ($code): string => trim((string) $code), (array) ($criteria['options'] ?? [])), fn (string $code): bool => $code !== '');

        $locations = $this->queries->locations();
        $index = $this->variants->searchIndex((string) ($criteria['q'] ?? ''), $style !== '' ? $style : null);
        // Lựa chọn theo từng loại tuỳ chọn lấy từ kết quả tìm (trước khi lọc tuỳ chọn) để chỉ hiện giá trị có thật.
        $found = [];
        foreach ($index as $row) {
            foreach ($row['options'] as $type => $code) {
                $found[$type][$code] = true;
            }
        }
        $optionFilters = [];
        foreach ($this->variants->optionTypes() as $type => $name) {
            if (isset($found[$type])) {
                $optionFilters[] = ['code' => (string) $type, 'name' => $name, 'values' => array_map('strval', array_keys($found[$type]))];
            }
        }

        $online = array_values(array_map(fn (Location $location): int => $location->id, array_filter($locations, fn (Location $location): bool => $location->status === 'active' && $location->ships_online_orders)));
        $sellable = $this->queries->sellableByVariant($online);
        $threshold = $this->lowStock->value();
        // Sản phẩm không theo dõi tồn: luôn bán được — không thuộc bộ lọc hết hàng/sắp hết.
        $untracked = array_flip($this->variants->untrackedInventory(array_column($index, 'id')));

        $rows = [];
        foreach ($index as $row) {
            foreach ($wanted as $type => $code) {
                if (($row['options'][$type] ?? null) !== $code) {
                    continue 2;
                }
            }
            $row['sellable'] = $sellable[$row['id']] ?? 0;
            $row['tracked'] = ! isset($untracked[$row['id']]);
            if ($filter !== 'all' && ! $row['tracked']) {
                continue;
            }
            if (($filter === 'out' && $row['sellable'] > 0) || ($filter === 'low' && ($row['sellable'] === 0 || $row['sellable'] > $threshold))) {
                continue;
            }
            $rows[] = $row;
        }

        return ['rows' => $rows, 'locations' => $locations, 'option_filters' => $optionFilters];
    }
}
