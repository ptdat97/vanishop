<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

/**
 * Luật hoàn tiền (docs/10-payment/payment.md §3): tổng hoàn không vượt số đã thu; chỉ hoàn khoản đang giữ tiền; hoàn tự
 * động qua cổng khi cổng hỗ trợ (hoàn một phần cần cổng hỗ trợ hoàn một phần), không thì chờ nhân viên/kênh chuyển trả.
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

    /**
     * @param  int|null  $requested  null = hoàn hết phần còn lại (chưa thu hoặc không còn gì → bỏ qua, không lỗi)
     */
    public static function decide(PaymentStatus $status, int $collected, int $alreadyRefunded, ?int $requested): RefundDecision
    {
        $refundable = self::refundable($collected, $alreadyRefunded);
        if ($requested === null) {
            if (! $status->hasCollected() || $refundable <= 0) {
                return RefundDecision::skip();
            }
            $requested = $refundable;
        }
        if (! $status->hasCollected()) {
            return RefundDecision::notCollected();
        }

        return $requested <= 0 || $requested > $refundable ? RefundDecision::exceeds($refundable) : RefundDecision::accept($requested, $refundable);
    }

    public static function isAutomatic(bool $gatewayRefunds, bool $gatewayRefundsPartially, int $amount, int $refundable): bool
    {
        return $gatewayRefunds && ($amount === $refundable || $gatewayRefundsPartially);
    }
}
