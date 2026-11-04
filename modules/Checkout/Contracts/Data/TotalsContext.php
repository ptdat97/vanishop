<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

use Modules\Promotion\Contracts\Data\PromotionResult;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Domain\Weight\Weight;

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
        return new self($this->customerId, $this->currencyCode, $lines, $this->adjustments, $this->voucherCodes,
            $this->shippingMethod, $this->shippingAddress, $this->now, $this->shipping, $this->promotions, $this->attributes);
    }

    public function withAdjustment(Adjustment $adjustment): self
    {
        return new self($this->customerId, $this->currencyCode, $this->lines, [...$this->adjustments, $adjustment], $this->voucherCodes,
            $this->shippingMethod, $this->shippingAddress, $this->now, $this->shipping, $this->promotions, $this->attributes);
    }

    public function withShipping(?ShippingOption $shipping): self
    {
        return new self($this->customerId, $this->currencyCode, $this->lines, $this->adjustments, $this->voucherCodes,
            $this->shippingMethod, $this->shippingAddress, $this->now, $shipping, $this->promotions, $this->attributes);
    }

    public function withPromotions(PromotionResult $promotions): self
    {
        return new self($this->customerId, $this->currencyCode, $this->lines, $this->adjustments, $this->voucherCodes,
            $this->shippingMethod, $this->shippingAddress, $this->now, $this->shipping, $promotions, $this->attributes);
    }

    public function money(int $amount): Money
    {
        return Money::of($amount, $this->currencyCode);
    }

    /**
     * Khối lượng hàng sẽ giao (gram, §3.10-2): tổng các dòng của đơn — chỉ dòng giỏ đã chọn. Cho ShippingRateProvider.
     */
    public function weight(): Weight
    {
        $weight = Weight::zero();
        foreach ($this->lines as $line) {
            $weight = $weight->add($line->weight());
        }

        return $weight;
    }

    /** Có dòng chưa nhập khối lượng → weight() thấp hơn thực tế; nhà vận chuyển tự quyết (khối lượng mặc định, từ chối…). */
    public function missingWeight(): bool
    {
        foreach ($this->lines as $line) {
            if ($line->weightGram === null) {
                return true;
            }
        }

        return false;
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
