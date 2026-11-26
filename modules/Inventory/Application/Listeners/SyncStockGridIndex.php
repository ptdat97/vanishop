<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Listeners;

use Modules\Catalog\Events\ProductListingProjected;
use Modules\Inventory\Application\StockGridIndexer;
use Modules\Inventory\Events\AvailabilityChanged;

/**
 * Giữ read model lưới tồn khớp Catalog và tồn kho (db-refactor G3). Chạy ngay sau commit (không qua hàng đợi): nhân viên sửa
 * tồn rồi xem lại lưới phải thấy số mới.
 */
final class SyncStockGridIndex
{
    public function __construct(private readonly StockGridIndexer $indexer) {}

    public function handle(AvailabilityChanged|ProductListingProjected $event): void
    {
        $event instanceof ProductListingProjected
            ? $this->indexer->syncProducts($event->productIds)
            : $this->indexer->syncSellable($event->variantIds);
    }
}
