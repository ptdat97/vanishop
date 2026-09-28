<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

use DateTimeImmutable;
use Modules\Shared\Domain\Money\Money;

final readonly class PaymentData
{
    public function __construct(
        public string $publicId,
        public string $gatewayCode,
        public string $orderNumber,
        public int $legalEntityId,
        public int $brandId,
        public Money $amount,
        public string $status,
        public ?string $gatewayReference,
        public ?DateTimeImmutable $expiresAt,
    ) {}
}
