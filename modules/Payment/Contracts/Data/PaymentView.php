<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts\Data;

final readonly class PaymentView
{
    public function __construct(
        public string $publicId,
        public string $gatewayCode,
        public string $status,
        public int $amount,
        public string $currencyCode,
        public string $orderPublicId,
        public string $orderNumber,
        public ?string $expiresAt,
        public ?PaymentInitiation $action,
        public ?string $actionError = null,
        /** Khách bấm "Thanh toán lại" được (0.3.75): đang chờ/lỗi, còn hạn, cổng online. */
        public bool $retryable = false,
    ) {}
}
