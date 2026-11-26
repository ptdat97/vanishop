<?php

declare(strict_types=1);

namespace Modules\Inventory\Console;

use Illuminate\Console\Command;
use Modules\Inventory\Application\StockGridIndexer;

/**
 * Tính lại số bán được trong read model lưới tồn cho mọi SKU (sửa lệch; sau khi khôi phục dữ liệu). Phần dữ liệu Catalog của
 * lưới dựng lại theo `vani:catalog:rebuild-listing`. Chạy lại an toàn.
 */
final class SyncStockGridCommand extends Command
{
    protected $signature = 'vani:inventory:sync-stock-grid';

    protected $description = 'Tính lại số bán được của lưới tồn Admin cho mọi SKU (chạy lại an toàn)';

    public function handle(StockGridIndexer $indexer): int
    {
        $this->info('Đã tính lại '.$indexer->syncAllSellable().' SKU.');

        return self::SUCCESS;
    }
}
