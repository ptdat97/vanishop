<?php

declare(strict_types=1);

namespace Modules\Cart\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

/**
 * Giỏ để hiển thị. `subtotal`/`itemCount` cộng mọi dòng bán được theo giá hiện tại (cả dòng chưa chọn);
 * `selectedLines()`/`selectedSubtotal()` là phần sẽ đặt hàng (0.3.64). Tổng cuối cùng
 * (khuyến mãi, phí vận chuyển, thuế) do totals pipeline của Checkout tính.
 */
final readonly class CartView
{
    /**
     * @param  list<CartLineView>  $lines
     */
    public function __construct(
        public string $id,
        public string $status,
        public string $currencyCode,
        public array $lines,
        public Money $subtotal,
        public int $itemCount,
    ) {}

    /**
     * Dòng sẽ vào đơn khi đặt hàng.
     *
     * @return list<CartLineView>
     */
    public function selectedLines(): array
    {
        return array_values(array_filter($this->lines, fn (CartLineView $line): bool => $line->selected));
    }

    public function selectedSubtotal(): Money
    {
        $subtotal = Money::zero($this->currencyCode);
        foreach ($this->selectedLines() as $line) {
            if ($line->lineTotal !== null && $line->variant !== null) {
                $subtotal = $subtotal->add($line->lineTotal);
            }
        }

        return $subtotal;
    }

    /**
     * Đặt được khi giỏ mở, có ít nhất một dòng đã chọn và không dòng đã chọn nào thiếu hàng. Dòng chưa chọn không chặn.
     */
    public function isCheckoutReady(): bool
    {
        $selected = $this->selectedLines();
        if ($this->status !== 'active' || $selected === []) {
            return false;
        }

        foreach ($selected as $line) {
            if ($line->blocksCheckout()) {
                return false;
            }
        }

        return true;
    }
}
