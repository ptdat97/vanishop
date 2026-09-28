<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Không đủ hàng để giữ (lỗi nghiệp vụ công khai cho Checkout).
 */
final class StockUnavailable extends BusinessRuleViolation
{
    public function __construct(
        public readonly int $variantId,
        public readonly int $requested,
        public readonly int $available,
    ) {
        parent::__construct("Không đủ hàng cho variant #{$variantId}: cần {$requested}, còn {$available}.");
    }

    public function errorCode(): string
    {
        return 'inventory.insufficient_stock';
    }

    public function details(): array
    {
        return ['variant_id' => $this->variantId];
    }
}
