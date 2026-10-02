<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

/**
 * Dữ liệu để cổng quyết định có khả dụng không (số tiền…).
 */
final readonly class PaymentContext
{
    public function __construct(
        public Money $amount,
    ) {}
}
