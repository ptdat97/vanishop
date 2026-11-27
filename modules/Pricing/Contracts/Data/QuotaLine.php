<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts\Data;

/** Dòng hàng đang mua theo giá của bảng giá `priceListCode` (Checkout gửi để kiểm/trừ suất). */
final readonly class QuotaLine
{
    public function __construct(
        public int $variantId,
        public int $productId,
        public int $quantity,
        public ?string $priceListCode,
    ) {}
}
