<?php

declare(strict_types=1);

namespace Modules\Catalog\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Application\Search\ProductListingProjector;

/**
 * Dựng lại read model trang danh sách (`product_listing`, `product_facet_values` — db-refactor §4.3) cho mọi sản phẩm hoặc
 * một sản phẩm. Chạy sau `migrate:fresh --seed` và khi deploy đổi logic của ProductListingProjector. Giữ `list_price_min`
 * (Pricing) và `in_stock` (Inventory tính lại qua event); sản phẩm mới chưa có giá thì chạy thêm
 * `vani:pricing:sync-list-prices`. Chạy lại an toàn.
 */
final class RebuildListingCommand extends Command
{
    protected $signature = 'vani:catalog:rebuild-listing {--product= : Chỉ dựng lại sản phẩm có id này}';

    protected $description = 'Dựng lại read model trang danh sách sản phẩm (chạy lại an toàn)';

    private const CHUNK = 500;

    public function handle(ProductListingProjector $listing): int
    {
        if ($this->option('product') !== null) {
            $listing->project((int) $this->option('product'));
            $this->info('Đã dựng lại sản phẩm '.(int) $this->option('product').'.');

            return self::SUCCESS;
        }

        $count = 0;
        DB::table('products')->select('id')->orderBy('id')->chunkById(self::CHUNK, function ($rows) use ($listing, &$count): void {
            $listing->project($rows->pluck('id')->map(fn ($id): int => (int) $id)->all());
            $count += $rows->count();
        });
        // Sản phẩm đã xoá mà còn dòng (dựng lại sau khi khôi phục dữ liệu không qua ứng dụng).
        DB::table('product_listing')->whereNotIn('product_id', DB::table('products')->select('id'))->delete();
        $this->info("Đã dựng lại read model cho {$count} sản phẩm.");

        return self::SUCCESS;
    }
}
