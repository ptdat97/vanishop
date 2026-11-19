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
        parent::__construct(__('inventory::texts.stock_unavailable.not_enough_stock_variant_variant', ['variant_id' => $variantId, 'requested' => $requested, 'available' => $available]));
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
