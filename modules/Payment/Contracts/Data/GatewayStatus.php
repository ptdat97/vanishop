<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use Modules\Shared\Domain\Money\Money;

final readonly class GatewayStatus
{
    public function __construct(
        public string $status,
        public ?string $gatewayTransactionId = null,
        public ?Money $amount = null,
    ) {}
}
