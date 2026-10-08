<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

/**
 * Các tầng giá của đơn (roadmap Phase 8, 0.3.37), tính từ snapshot (không đổi khi catalog/khuyến mãi đổi):
 *
 *   giá niêm yết − giảm giá bán = tạm tính
 *   tạm tính − khuyến mãi tự động − mã giảm giá − giảm khác + phí giao = tổng   (thuế VAT đã gồm trong tổng)
 *
 * "Giảm giá bán" = chênh niêm yết → giá bán của bảng giá sale/thành viên (`priceLists` theo mã bảng giá). "Giảm khác" =
 * bù giá trị hàng trả khi đổi hàng và phần không tách được theo khuyến mãi (đơn trước 0.3.31). Mọi số là đồng (VND).
 */
final readonly class PriceBreakdown
{
    /**
     * @param  list<array{promotion_id: int, name: string, code: ?string, voucher: bool, amount: int}>  $promotions
     * @param  list<array{code: string, markdown: int}>  $priceLists
     */
    public function __construct(
        public int $listAmount,
        public int $markdown,
        public int $subtotal,
        public int $promotionDiscount,
        public int $voucherDiscount,
        public int $otherDiscount,
        public int $shipping,
        public int $taxIncluded,
        public int $total,
        public array $promotions,
        public array $priceLists,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'list_amount' => $this->listAmount, 'markdown' => $this->markdown, 'subtotal' => $this->subtotal,
            'promotion_discount' => $this->promotionDiscount, 'voucher_discount' => $this->voucherDiscount, 'other_discount' => $this->otherDiscount,
            'shipping' => $this->shipping, 'tax_included' => $this->taxIncluded, 'total' => $this->total,
            'promotions' => $this->promotions, 'price_lists' => $this->priceLists,
        ];
    }
}
