<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use RuntimeException;

/**
 * Không đủ hàng để giữ (lỗi nghiệp vụ công khai cho Checkout).
 */
final class StockUnavailable extends RuntimeException
{
    public function __construct(
        public readonly int $variantId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct("Không đủ hàng cho variant #{$variantId}: cần {$requested}, còn {$available}.");
    }
}
