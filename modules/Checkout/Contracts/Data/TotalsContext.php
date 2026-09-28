<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

use Modules\Promotion\Contracts\Data\PromotionResult;
use Modules\Shared\Domain\Money\Money;

/**
 * Trạng thái bất biến chạy qua các TotalsCalculator. Mỗi calculator trả bản mới qua with*().
 */
final readonly class TotalsContext
{
    /**
     * @param  list<TotalsLine>  $lines
     * @param  list<Adjustment>  $adjustments
     * @param  list<string>  $voucherCodes
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public int $channelId,
        public ?int $customerId,
        public string $currencyCode,
        public array $lines,
        public array $adjustments,
        public array $voucherCodes,
        public ?string $shippingMethod,
        public ?array $shippingAddress,
        public int $now,
        public ?ShippingOption $shipping = null,
        public ?PromotionResult $promotions = null,
        public array $attributes = [],
    ) {}

    /**
     * @param  list<TotalsLine>  $lines
     */
    public function withLines(array $lines): self
    {
        return new self($this->channelId, $this->customerId, $this->currencyCode, $lines, $this->adjustments, $this->voucherCodes,
            $this->shippingMethod, $this->shippingAddress, $this->now, $this->shipping, $this->promotions, $this->attributes);
    }

    public function withAdjustment(Adjustment $adjustment): self
    {
        return new self($this->channelId, $this->customerId, $this->currencyCode, $this->lines, [...$this->adjustments, $adjustment], $this->voucherCodes,
            $this->shippingMethod, $this->shippingAddress, $this->now, $this->shipping, $this->promotions, $this->attributes);
    }

    public function withShipping(?ShippingOption $shipping): self
    {
        return new self($this->channelId, $this->customerId, $this->currencyCode, $this->lines, $this->adjustments, $this->voucherCodes,
            $this->shippingMethod, $this->shippingAddress, $this->now, $shipping, $this->promotions, $this->attributes);
    }

    public function withPromotions(PromotionResult $promotions): self
    {
        return new self($this->channelId, $this->customerId, $this->currencyCode, $this->lines, $this->adjustments, $this->voucherCodes,
            $this->shippingMethod, $this->shippingAddress, $this->now, $this->shipping, $promotions, $this->attributes);
    }

    public function money(int $amount): Money
    {
        return Money::of($amount, $this->currencyCode);
    }

    public function linesTotal(): Money
    {
        $total = $this->money(0);
        foreach ($this->lines as $line) {
            $total = $total->add($line->total());
        }

        return $total;
    }
}
