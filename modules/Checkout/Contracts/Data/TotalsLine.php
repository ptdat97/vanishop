<?php

declare(strict_types=1);

namespace Modules\Checkout\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

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
        public ?string $colorName,
        public string $sizeCode,
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
    ) {}

    public function total(): Money
    {
        return $this->subtotal->subtract($this->discount);
    }

    public function withDiscount(Money $discount): self
    {
        return new self($this->key, $this->variantId, $this->brandId, $this->styleId, $this->sku, $this->name, $this->colorName, $this->sizeCode,
            $this->imageUrl, $this->quantity, $this->unitPrice, $this->compareAt, $this->subtotal, $discount, $this->taxRateBp, $this->tax, $this->brandName, $this->options);
    }

    public function withTax(int $rateBp, Money $tax): self
    {
        return new self($this->key, $this->variantId, $this->brandId, $this->styleId, $this->sku, $this->name, $this->colorName, $this->sizeCode,
            $this->imageUrl, $this->quantity, $this->unitPrice, $this->compareAt, $this->subtotal, $this->discount, $rateBp, $tax, $this->brandName, $this->options);
    }
}
