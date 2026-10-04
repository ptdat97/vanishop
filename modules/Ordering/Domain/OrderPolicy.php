<?php

declare(strict_types=1);

namespace Modules\Ordering\Domain;

use Modules\Ordering\Contracts\Data\OrderStatus;

/**
 * Quy tắc thao tác trên đơn ngoài bảng chuyển trạng thái.
 */
final class OrderPolicy
{
    /** Khách tự huỷ: hàng chưa rời kho (vận đơn đã tạo nhưng chưa lấy hàng vẫn huỷ được). */
    public static function customerCanCancel(OrderStatus $status, string $fulfillmentStatus): bool
    {
        return in_array($status, [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing], true)
            && in_array($fulfillmentStatus, ['unfulfilled', 'allocated'], true);
    }

    /** Nhân viên huỷ: theo state machine, khi hàng chưa rời kho hoặc đã bị hoàn về. */
    public static function staffCanCancel(OrderStatus $status, string $fulfillmentStatus): bool
    {
        return OrderStateMachine::can($status, OrderStatus::Cancelled) && in_array($fulfillmentStatus, ['unfulfilled', 'allocated', 'returned_to_sender'], true);
    }

    /**
     * Huỷ một phần (nhân viên, 0.3.18): đơn đã xác nhận/đang xử lý, hàng chưa rời kho; tiền ở trạng thái điều chỉnh được —
     * COD chờ thu (giảm số thu hộ) hoặc đã thu (hoàn phần huỷ). Thanh toán online chưa trả: link đã cấp mang số tiền cũ → không.
     */
    public static function staffCanCancelLines(OrderStatus $status, string $fulfillmentStatus, string $paymentStatus): bool
    {
        return in_array($status, [OrderStatus::Confirmed, OrderStatus::Processing], true)
            && in_array($fulfillmentStatus, ['unfulfilled', 'allocated'], true)
            && in_array($paymentStatus, ['cod_pending', 'paid', 'partially_refunded'], true);
    }

    /** Đổi địa chỉ giao: hàng chưa rời kho, đơn còn mở. Vận đơn đã đặt ở hãng thì plugin hãng phải cập nhật lại. */
    public static function canChangeAddress(OrderStatus $status, string $fulfillmentStatus): bool
    {
        return in_array($status, [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing], true)
            && in_array($fulfillmentStatus, ['unfulfilled', 'allocated'], true);
    }
}
