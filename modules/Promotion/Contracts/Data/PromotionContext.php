<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

final readonly class PromotionContext
{
    /**
     * @param  list<PromotionLine>  $lines
     * @param  list<string>  $voucherCodes
     * @param  array<string, mixed>  $attributes  dữ liệu bổ sung (vd. creator_ref) do plugin đặt
     */
    public function __construct(
        public ?int $customerId,
        public string $currencyCode,
        public array $lines,
        public array $voucherCodes,
        public int $now,
        public array $attributes = [],
    ) {}

    public function subtotal(): Money
    {
        $total = Money::zero($this->currencyCode);
        foreach ($this->lines as $line) {
            $total = $total->add($line->subtotal);
        }

        return $total;
    }

    public function line(int $key): ?PromotionLine
    {
        foreach ($this->lines as $line) {
            if ($line->key === $key) {
                return $line;
            }
        }

        return null;
    }
}
