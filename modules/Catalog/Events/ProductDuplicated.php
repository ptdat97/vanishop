<?php

declare(strict_types=1);

namespace Modules\Catalog\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Domain event — sản phẩm được nhân bản thành bản nháp (stabilization A4). Module sở hữu dữ liệu theo SKU (giá niêm yết…)
 * chép sang SKU mới theo `$variantIds` (SKU nguồn => SKU mới). Dispatch sau khi transaction commit.
 */
final readonly class ProductDuplicated implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  array<int, int>  $variantIds  id SKU nguồn => id SKU mới
     */
    public function __construct(
        public int $sourceStyleId,
        public int $styleId,
        public array $variantIds,
    ) {}
}
