<?php

declare(strict_types=1);

namespace Modules\Ordering\Domain;

/**
 * Giá trị hợp lệ của orders.payment_status (chiều thanh toán, do Payment cập nhật).
 * `voided` (0.4.52): đơn huỷ khi chưa thu tiền — khoản chờ thu/chờ trả/giữ tiền đã huỷ, không còn gì để thu.
 */
final class PaymentStatus
{
    public const VALUES = ['unpaid', 'authorized', 'paid', 'partially_refunded', 'refunded', 'cod_pending', 'cod_collected', 'failed', 'voided'];

    public static function isValid(string $status): bool
    {
        return in_array($status, self::VALUES, true);
    }
}
