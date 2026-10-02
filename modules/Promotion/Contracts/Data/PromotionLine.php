<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

/**
 * Một dòng hàng khi đánh giá khuyến mãi. `key` = variant id (duy nhất trong giỏ).
 */
final readonly class PromotionLine
{
    public function __construct(
        public int $key,
        public ?int $brandId,   // thương hiệu (thuộc tính catalog) — cho rule "thuộc brand"
        public int $styleId,
        public int $quantity,
        public Money $unitPrice,
        public Money $subtotal,
        /** Variant của dòng (một variant có thể ở nhiều dòng khác tuỳ chọn — `key` là khoá dòng). */
        public ?int $variantId = null,
    ) {}
}
