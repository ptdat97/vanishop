<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

/**
 * Tổng hoàn không vượt số đã thu (docs/10-payment/payment.md §3).
 */
final class RefundRules
{
    public static function refundable(int $collected, int $alreadyRefunded): int
    {
        return max(0, $collected - $alreadyRefunded);
    }

    public static function statusAfterRefund(int $collected, int $refundedTotal): PaymentStatus
    {
        return $refundedTotal >= $collected ? PaymentStatus::Refunded : PaymentStatus::PartiallyRefunded;
    }
}
