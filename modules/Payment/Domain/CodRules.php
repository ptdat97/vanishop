<?php

declare(strict_types=1);

namespace Modules\Payment\Domain;

/**
 * Luật thu tiền khi giao (COD): nhân viên ghi nhận "Đã thu tiền" cho khoản COD đang chờ có số tiền > 0, đơn chưa huỷ;
 * huỷ một phần đơn COD chờ thu → giảm số cần thu (không âm).
 */
final class CodRules
{
    public static function canCollectManually(PaymentStatus $status, int $amount, bool $collectsOnDelivery): bool
    {
        return $status === PaymentStatus::Pending && $amount > 0 && $collectsOnDelivery;
    }

    public static function amountAfterPartialCancellation(int $amount, int $cancelled): int
    {
        return max(0, $amount - $cancelled);
    }
}
