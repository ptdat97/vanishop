<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts\Data;

/**
 * Giỏ vượt suất: `reason` = `sold_out` (còn ít suất hơn số lượng) | `customer_limit` (vượt giới hạn mỗi khách); `allowed` = số
 * lượng tối đa còn mua được theo giá này; `message` = câu cho khách.
 */
final readonly class QuotaViolation
{
    /**
     * @param  list<int>  $variantIds
     */
    public function __construct(
        public array $variantIds,
        public string $reason,
        public int $allowed,
        public string $message,
    ) {}
}
