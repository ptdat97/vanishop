<?php

declare(strict_types=1);

namespace Modules\Cart\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

/**
 * Giỏ để hiển thị. `subtotal` chỉ cộng các dòng bán được theo giá hiện tại; tổng cuối cùng
 * (khuyến mãi, phí vận chuyển, thuế) do totals pipeline của Checkout tính.
 */
final readonly class CartView
{
    /**
     * @param  list<CartLineView>  $lines
     */
    public function __construct(
        public string $id,
        public int $channelId,
        public string $status,
        public string $currencyCode,
        public array $lines,
        public Money $subtotal,
        public int $itemCount,
    ) {}

    public function isCheckoutReady(): bool
    {
        if ($this->status !== 'active' || $this->lines === []) {
            return false;
        }

        foreach ($this->lines as $line) {
            if ($line->blocksCheckout()) {
                return false;
            }
        }

        return true;
    }
}
