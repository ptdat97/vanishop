<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Money\Money;

/**
 * Một dòng hàng khi đánh giá khuyến mãi. `key` = variant id (duy nhất trong giỏ).
 */
#[PluginApi]
final readonly class PromotionLine
{
    public function __construct(
        public int $key,
        public ?int $brandId,   // thương hiệu (thuộc tính catalog) — cho rule "thuộc brand"
        public int $productId,
        public int $quantity,
        public Money $unitPrice,
        public Money $subtotal,
        /** Variant của dòng (một variant có thể ở nhiều dòng khác tuỳ chọn — `key` là khoá dòng). */
        public ?int $variantId = null,
        /** Giá gốc một đơn vị (giá gạch) khi dòng đang được giảm bằng bảng giá — mốc tính trần giảm (0.5.109). */
        public ?Money $compareAt = null,
        /** Bảng giá đã cho giá của dòng (0.5.109). */
        public ?string $priceListCode = null,
    ) {}

    /** Dòng đang bán dưới giá gốc (giá sale/thành viên hoặc giá gạch) — rule "không áp cho hàng đang giảm giá". */
    public function isDiscounted(): bool
    {
        return $this->compareAt !== null && $this->compareAt->greaterThan($this->unitPrice);
    }

    /** Thành tiền theo giá gốc: mốc của trần giảm (giá sàn) khi dòng đang giảm giá; không thì bằng thành tiền. */
    public function referenceSubtotal(): Money
    {
        return $this->compareAt !== null && $this->compareAt->greaterThan($this->unitPrice) ? $this->compareAt->multiply($this->quantity) : $this->subtotal;
    }
}
