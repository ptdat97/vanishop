<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Domain\Weight\Weight;

/**
 * Một dòng trong totals pipeline. `discount` là độ lớn (>= 0) đã phân bổ về dòng; `tax` là thuế ĐÃ GỒM trong total().
 */
final readonly class TotalsLine
{
    public function __construct(
        public int $key,
        public int $variantId,
        public ?int $brandId,
        public int $styleId,
        public string $sku,
        public string $name,
        /** @var list<array{type: string, name: string, code: string, value: string}> tổ hợp tuỳ chọn của SKU (ADR-045) — chụp vào dòng đơn */
        public array $variantOptions,
        public ?string $imageUrl,
        public int $quantity,
        public Money $unitPrice,
        public ?Money $compareAt,
        public Money $subtotal,
        public Money $discount,
        public int $taxRateBp = 0,
        public ?Money $tax = null,
        public ?string $brandName = null,
        /** @var array<string, array<string, scalar|null>> tuỳ chọn dòng theo plugin id (CartLineOption) */
        public array $options = [],
        /** Bảng giá của giá bán (0.3.37), lưu vào dòng đơn. */
        public ?string $priceListCode = null,
        /** Khối lượng một sản phẩm (gram, 0.3.73) — phí ship theo khối lượng; null = chưa nhập. */
        public ?int $weightGram = null,
    ) {}

    /**
     * Khối lượng cả dòng; dòng chưa nhập khối lượng tính 0 (xem TotalsContext::missingWeight()).
     */
    public function weight(): Weight
    {
        return $this->weightGram === null ? Weight::zero() : Weight::grams($this->weightGram)->multiply($this->quantity);
    }

    public function total(): Money
    {
        return $this->subtotal->subtract($this->discount);
    }

    public function withDiscount(Money $discount): self
    {
        return new self($this->key, $this->variantId, $this->brandId, $this->styleId, $this->sku, $this->name, $this->variantOptions,
            $this->imageUrl, $this->quantity, $this->unitPrice, $this->compareAt, $this->subtotal, $discount, $this->taxRateBp, $this->tax, $this->brandName, $this->options, $this->priceListCode, $this->weightGram);
    }

    public function withTax(int $rateBp, Money $tax): self
    {
        return new self($this->key, $this->variantId, $this->brandId, $this->styleId, $this->sku, $this->name, $this->variantOptions,
            $this->imageUrl, $this->quantity, $this->unitPrice, $this->compareAt, $this->subtotal, $this->discount, $rateBp, $tax, $this->brandName, $this->options, $this->priceListCode, $this->weightGram);
    }
}
