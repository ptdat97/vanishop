<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Application\StockGrid;

/**
 * Cột "Tồn bán được" ở danh sách sản phẩm Admin (slot vani.admin.product.list_columns, 0.3.103): tổng số bán được ở kho
 * giao online; "Hết hàng" khi 0, "sắp hết" khi ≤ ngưỡng. Sửa tồn ở lưới tồn kho (lọc theo mã sản phẩm).
 */
final class ProductListStock
{
    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly StockGrid $grid,
    ) {}

    /**
     * @param  list<int>  $styleIds
     * @return array{key: string, label: string, values: array<int, string>, urls: array<int, string>}|null
     */
    public function column(array $styleIds): ?array
    {
        if (! Gate::allows('inventory.view')) {
            return null;
        }
        $variantsByStyle = $this->variants->ofStyles($styleIds);
        $byStyle = array_map(fn (array $variants): array => array_map(fn ($variant): int => $variant->id, $variants), $variantsByStyle);
        $threshold = (int) config('vanishop.inventory.low_stock_threshold', 3);
        $urls = [];
        foreach ($variantsByStyle as $styleId => $variants) {
            if ($variants !== []) {
                $urls[$styleId] = route('admin.inventory.stock.index', ['style' => $variants[0]->styleCode]);
            }
        }

        return ['key' => 'stock', 'label' => 'Tồn bán được', 'values' => array_map(fn (int $sellable): string => match (true) {
            $sellable === 0 => 'Hết hàng',
            $sellable <= $threshold => "{$sellable} · sắp hết",
            default => (string) $sellable,
        }, $this->grid->sellableByStyle($byStyle)), 'urls' => $urls];
    }
}
