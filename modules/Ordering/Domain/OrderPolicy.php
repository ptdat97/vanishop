<?php

declare(strict_types=1);

namespace Modules\Ordering\Domain;

use Modules\Ordering\Contracts\Data\OrderStatus;

/**
 * Quy tắc thao tác trên đơn ngoài bảng chuyển trạng thái.
 */
final class OrderPolicy
{
    /** Khách tự huỷ: chưa xử lý kho và chưa xuất hàng. */
    public static function customerCanCancel(OrderStatus $status, string $fulfillmentStatus): bool
    {
        return in_array($status, [OrderStatus::Pending, OrderStatus::Confirmed], true) && $fulfillmentStatus === 'unfulfilled';
    }

    /** Nhân viên huỷ: theo state machine, và chỉ khi hàng chưa rời kho. */
    public static function staffCanCancel(OrderStatus $status, string $fulfillmentStatus): bool
    {
        return OrderStateMachine::can($status, OrderStatus::Cancelled) && in_array($fulfillmentStatus, ['unfulfilled', 'allocated'], true);
    }

    /** Đổi địa chỉ giao: trước khi fulfillment bắt đầu, đơn còn mở. */
    public static function canChangeAddress(OrderStatus $status, string $fulfillmentStatus): bool
    {
        return in_array($status, [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing], true) && $fulfillmentStatus === 'unfulfilled';
    }
}
