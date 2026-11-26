<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Dựng lại read model trang danh sách cho một lô sản phẩm ngoài request (đổi loại tuỳ chọn, chuyển cây danh mục — có thể
 * chạm hàng chục nghìn sản phẩm). Idempotent nên thử lại được.
 */
final class RebuildProductListingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    /**
     * @param  list<int>  $productIds
     */
    public function __construct(public readonly array $productIds)
    {
        $this->onQueue('catalog-sync');
    }

    public function handle(ProductListingProjector $listing): void
    {
        $listing->project($this->productIds);
    }
}
