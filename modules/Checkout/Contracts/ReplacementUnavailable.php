<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Không tạo được đơn thay thế: variant không còn bán, không đủ hàng (0.3.32) hoặc không có cổng thu khi giao để thu phần
 * chênh (`no_collect_on_delivery`, 0.3.39).
 */
final class ReplacementUnavailable extends BusinessRuleViolation
{
    /**
     * @param  list<int>  $variantIds
     */
    public function __construct(
        public readonly array $variantIds,
        public readonly string $reason,
    ) {
        parent::__construct(match ($reason) {
            'insufficient_stock' => __('checkout::texts.replacement_unavailable.not_enough_stock_exchange_variant').implode(', ', $variantIds).').',
            'no_collect_on_delivery' => __('checkout::texts.replacement_unavailable.no_cash_delivery_gateway_enabled'),
            default => __('checkout::texts.replacement_unavailable.exchanged_product_no_longer_sold').implode(', ', $variantIds).').',
        });
    }

    public function errorCode(): string
    {
        return 'checkout.replacement_unavailable';
    }

    public function details(): array
    {
        return ['variant_ids' => $this->variantIds, 'reason' => $this->reason];
    }
}
