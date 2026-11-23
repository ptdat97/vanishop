<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

/**
 * @phpstan-type RecheckConfig array{action_type: string, action_config: array<string, mixed>, rules: list<array{type: string, config: array<string, mixed>}>}
 */
final readonly class AppliedPromotion
{
    /**
     * @param  array<int, Money>  $lineDiscounts  khoá dòng => số tiền giảm (>= 0)
     * @param  RecheckConfig|null  $recheckConfig
     */
    public function __construct(
        public int $promotionId,
        public ?int $voucherId,
        public ?string $voucherCode,
        public string $name,
        public array $lineDiscounts,
        public Money $total,
        public ?array $recheckConfig = null,
    ) {}
}
