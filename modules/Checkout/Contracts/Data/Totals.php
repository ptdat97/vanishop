<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

use Modules\Promotion\Contracts\Data\PromotionResult;
use Modules\Promotion\Contracts\Data\VoucherRejection;
use Modules\Shared\Domain\Money\Money;

/**
 * Kết quả cuối của totals pipeline. grandTotal = Σ line total + shipping. Thuế đã gồm trong giá.
 */
final readonly class Totals
{
    /**
     * @param  list<TotalsLine>  $lines
     * @param  list<Adjustment>  $adjustments
     * @param  list<VoucherRejection>  $rejectedVouchers
     */
    public function __construct(
        public string $currencyCode,
        public array $lines,
        public array $adjustments,
        public Money $subtotal,
        public Money $discount,
        public ?ShippingOption $shipping,
        public Money $tax,
        public Money $grandTotal,
        public array $rejectedVouchers,
        public ?PromotionResult $promotions = null,
        public int $channelId = 0,
    ) {}

    public function shippingFee(): Money
    {
        return $this->shipping?->fee ?? Money::zero($this->currencyCode);
    }

    /**
     * @return list<int>
     */
    public function brandIds(): array
    {
        return array_values(array_unique(array_map(fn (TotalsLine $line): int => $line->brandId, $this->lines)));
    }
}
