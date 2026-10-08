<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

/**
 * Một dòng của đơn thay thế (đổi hàng). `creditAmount` = phần giá trị hàng trả được bù vào dòng này (giảm giá loại
 * `exchange_credit`), ≤ unitAmount × quantity.
 */
final readonly class ReplacementLine
{
    public function __construct(
        public int $variantId,
        public int $quantity,
        public int $unitAmount,
        public int $creditAmount,
    ) {}
}
