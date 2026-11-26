<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Listeners;

use Modules\Catalog\Events\ProductListingProjected;
use Modules\Pricing\Application\PriceVariantIndexer;

/**
 * Giữ read model SKU của màn hình bảng giá khớp Catalog (db-refactor L55). Chạy ngay sau commit: SKU vừa tạo phải tìm được ở
 * trang bảng giá.
 */
final class SyncPriceVariantIndex
{
    public function __construct(private readonly PriceVariantIndexer $indexer) {}

    public function handle(ProductListingProjected $event): void
    {
        $this->indexer->syncProducts($event->productIds);
    }
}
