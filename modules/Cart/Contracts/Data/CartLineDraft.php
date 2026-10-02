<?php

declare(strict_types=1);

namespace Modules\Cart\Contracts\Data;

/**
 * Dòng giỏ sắp được ghi (tham số của hook vani.cart.validate_line). `quantity` là số lượng SAU thay đổi.
 */
final readonly class CartLineDraft
{
    public function __construct(
        public string $cartId,
        public ?int $customerId,
        public int $variantId,
        public ?int $brandId,
        public int $quantity,
        public int $unitPrice,
        /** @var array<string, array<string, scalar|null>> */
        public array $options = [],
    ) {}
}
