<?php

declare(strict_types=1);

namespace Modules\Catalog\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Read model trang danh sách của các sản phẩm này vừa được dựng lại (db-refactor §4.3) — dispatch sau commit. Inventory nghe
 * để tính lại `in_stock` (SKU mới, SKU đổi trạng thái, `vani:catalog:rebuild-listing`).
 */
final readonly class ProductListingProjected implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<int>  $productIds
     */
    public function __construct(public array $productIds) {}
}
