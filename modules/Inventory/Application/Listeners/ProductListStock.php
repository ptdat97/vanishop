<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Application\LowStockThreshold;
use Modules\Inventory\Application\StockGrid;
use Modules\Inventory\Application\StockQueries;

/**
 * Cột "Tồn bán được" ở danh sách sản phẩm Admin (slot vani.admin.product.list_columns, 0.3.103): tổng số bán được ở kho
 * giao online; "Hết hàng" khi 0, "sắp hết" khi ≤ ngưỡng. Cửa hàng một kho do VaniShop quản lý tồn: sửa
 * tồn ngay trong ô (từng SKU, lưu qua endpoint lưu nhiều ô của lưới tồn); còn lại sửa ở lưới tồn kho (lọc theo mã sản phẩm).
 */
final class ProductListStock
{
    /** Sản phẩm nhiều biến thể hơn mức này không sửa trong ô (ô quá dài) — mở lưới tồn. */
    public const MAX_INLINE_VARIANTS = 24;

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly StockGrid $grid,
        private readonly LowStockThreshold $lowStock,
        private readonly StockQueries $queries,
    ) {}

    /**
     * @param  list<int>  $productIds
     * @return array{key: string, label: string, values: array<int, string>, urls: array<int, string>, edit_values: array<int, array{url: string, location_id: int, rows: list<array{variant_id: int, label: string, on_hand: int}>}|null>}|null
     */
    public function column(array $productIds): ?array
    {
        if (! Gate::allows('inventory.view')) {
            return null;
        }
        $variantsByProduct = $this->variants->ofProducts($productIds);
        $byProduct = array_map(fn (array $variants): array => array_map(fn ($variant): int => $variant->id, $variants), $variantsByProduct);
        $threshold = $this->lowStock->value();
        $urls = [];
        foreach ($variantsByProduct as $productId => $variants) {
            if ($variants !== []) {
                $urls[$productId] = route('admin.inventory.stock.index', ['product' => $variants[0]->productCode]);
            }
        }

        // Sản phẩm không theo dõi tồn (luôn bán được): nhãn riêng, không sửa tồn trong ô.
        $untracked = array_keys(array_filter($variantsByProduct, fn (array $variants): bool => $variants !== [] && ! $variants[0]->tracksInventory));
        $values = array_map(fn (int $sellable): string => match (true) {
            $sellable === 0 => __('inventory::texts.product_list_stock.out_stock'),
            $sellable <= $threshold => __('inventory::texts.product_list_stock.sellable_low_stock', ['sellable' => $sellable]),
            default => (string) $sellable,
        }, $this->grid->sellableByProduct($byProduct));
        $editValues = $this->editValues($variantsByProduct);
        foreach ($untracked as $productId) {
            $values[$productId] = __('inventory::texts.product_list_stock.stock_not_tracked');
            $editValues[$productId] = null;
        }

        return ['key' => 'stock', 'label' => __('inventory::texts.product_list_stock.sellable_stock'), 'values' => $values, 'urls' => $urls, 'edit_values' => $editValues];
    }

    /**
     * Dữ liệu sửa tồn trong ô: chỉ khi nhân viên được điều chỉnh tồn và cửa hàng có đúng một kho do VaniShop quản lý tồn
     * (SME). Sản phẩm quá nhiều biến thể sửa ở lưới tồn.
     *
     * @param  array<int, list<VariantData>>  $variantsByProduct
     * @return array<int, array{url: string, location_id: int, rows: list<array{variant_id: int, label: string, on_hand: int}>}|null>
     */
    private function editValues(array $variantsByProduct): array
    {
        $locations = $this->queries->locations();
        if (! Gate::allows('inventory.adjust') || count($locations) !== 1 || ! $locations[0]->managesOnHand() || $locations[0]->status !== 'active') {
            return [];
        }

        $location = $locations[0];
        $levels = $this->queries->levels(array_merge([], ...array_map(fn (array $variants): array => array_map(fn (VariantData $variant): int => $variant->id, $variants), array_values($variantsByProduct))));
        $url = route('admin.inventory.stock.batch');
        $result = [];
        foreach ($variantsByProduct as $productId => $variants) {
            $result[$productId] = $variants === [] || count($variants) > self::MAX_INLINE_VARIANTS ? null : [
                'url' => $url, 'location_id' => $location->id,
                'rows' => array_map(fn (VariantData $variant): array => [
                    'variant_id' => $variant->id, 'label' => VariantOptionsText::label($variant->options),
                    'on_hand' => $levels[$location->id.':'.$variant->id]['on_hand'] ?? 0,
                ], $variants),
            ];
        }

        return $result;
    }
}
