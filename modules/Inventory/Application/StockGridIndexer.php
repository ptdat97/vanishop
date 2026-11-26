<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Data\VariantIndexRows;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Ghi read model của lưới tồn (`stock_grid_index`, `stock_grid_options` — db-refactor G3). Phần lấy từ Catalog (tên, mã,
 * thứ tự SKU, trạng thái, tuỳ chọn) dựng lại theo sản phẩm khi Catalog dựng read model của mình (event
 * ProductListingProjected); `sellable` cập nhật theo SKU khi tồn đổi (AvailabilityChanged) và cho mọi SKU khi kho đổi cờ giao
 * online. `vani:inventory:sync-stock-grid` tính lại `sellable` toàn bộ.
 */
final class StockGridIndexer
{
    private const CHUNK = 500;

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly StockQueries $queries,
    ) {}

    /**
     * @param  list<int>  $productIds
     */
    public function syncProducts(array $productIds): void
    {
        foreach (array_chunk(array_values(array_unique($productIds)), self::CHUNK) as $chunk) {
            $byProduct = $this->variants->ofProducts($chunk);
            $old = DB::table('stock_grid_index')->whereIn('product_id', $chunk)->pluck('variant_id')->map(fn ($id): int => (int) $id)->all();
            $rows = [];
            $options = [];
            foreach (VariantIndexRows::from($byProduct) as $row) {
                foreach ($row['options'] as $option) {
                    $options[] = ['type_code' => $option['type'], 'value_code' => $option['code'], 'variant_id' => $row['variant_id']];
                }
                unset($row['options']);
                $rows[] = [...$row, 'sellable' => 0];
            }
            $ids = array_column($rows, 'variant_id');
            DB::transaction(function () use ($old, $ids, $rows, $options): void {
                $all = array_values(array_unique([...$old, ...$ids]));
                DB::table('stock_grid_options')->whereIn('variant_id', $all)->delete();
                DB::table('stock_grid_index')->whereIn('variant_id', $all)->delete();
                foreach (array_chunk($rows, 1000) as $part) {
                    DB::table('stock_grid_index')->insert($part);
                }
                foreach (array_chunk($options, 1000) as $part) {
                    DB::table('stock_grid_options')->insertOrIgnore($part);
                }
            });
            $this->syncSellable($ids);
        }
    }

    /**
     * @param  list<int>  $variantIds
     */
    public function syncSellable(array $variantIds): void
    {
        $online = $this->onlineLocationIds();
        foreach (array_chunk(array_values(array_unique($variantIds)), self::CHUNK) as $chunk) {
            $sellable = $online === [] ? [] : DB::table('stock_levels')->whereIn('location_id', $online)->whereIn('variant_id', $chunk)
                ->whereRaw('on_hand > reserved + safety_stock')->groupBy('variant_id')
                ->selectRaw('variant_id, sum(on_hand - reserved - safety_stock) as sellable')->pluck('sellable', 'variant_id')->all();
            $current = DB::table('stock_grid_index')->whereIn('variant_id', $chunk)->pluck('sellable', 'variant_id');
            foreach ($current as $variantId => $before) {
                $after = (int) ($sellable[$variantId] ?? 0);
                if ((int) $before !== $after) {
                    DB::table('stock_grid_index')->where('variant_id', $variantId)->update(['sellable' => $after]);
                }
            }
        }
    }

    /** Tính lại `sellable` cho mọi SKU (kho đổi cờ giao online / trạng thái, sửa lệch). */
    public function syncAllSellable(): int
    {
        $count = 0;
        DB::table('stock_grid_index')->select('variant_id')->orderBy('variant_id')->chunkById(self::CHUNK * 4, function ($rows) use (&$count): void {
            $this->syncSellable($rows->pluck('variant_id')->map(fn ($id): int => (int) $id)->all());
            $count += $rows->count();
        }, 'variant_id');

        return $count;
    }

    /**
     * @return list<int>
     */
    private function onlineLocationIds(): array
    {
        return array_values(array_map(fn (Location $location): int => $location->id, array_filter(
            $this->queries->locations(),
            fn (Location $location): bool => $location->status === 'active' && $location->ships_online_orders,
        )));
    }
}
