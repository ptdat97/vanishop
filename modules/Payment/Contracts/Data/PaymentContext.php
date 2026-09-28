<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

/**
 * Dữ liệu để cổng quyết định có khả dụng không (theo brand, pháp nhân, số tiền…).
 */
final readonly class PaymentContext
{
    public function __construct(
        public int $brandId,
        public int $legalEntityId,
        public int $channelId,
        public Money $amount,
    ) {}
}
