<?php

declare(strict_types=1);

namespace Modules\Pricing\Domain;

use Modules\Shared\Domain\Money\Money;

final readonly class SelectedPrice
{
    public function __construct(
        public Money $amount,
        public ?Money $compareAt,
        public int $priceListId,
        public string $priceListCode,
    ) {}

    /**
     * % giảm so với giá gốc, làm tròn xuống (không phóng đại mức giảm). null khi không có giá gốc.
     */
    public function discountPercent(): ?int
    {
        if ($this->compareAt === null || ! $this->compareAt->greaterThan($this->amount)) {
            return null;
        }

        return intdiv(($this->compareAt->amount - $this->amount->amount) * 100, $this->compareAt->amount);
    }
}
