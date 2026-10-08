<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts;

use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Không tạo được đơn thay thế: variant không còn bán hoặc không đủ hàng (0.3.32).
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
        parent::__construct(($reason === 'insufficient_stock' ? 'Không đủ hàng để đổi' : 'Sản phẩm đổi không còn bán').' (variant '.implode(', ', $variantIds).').');
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
