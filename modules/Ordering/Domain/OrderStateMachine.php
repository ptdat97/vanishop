<?php

declare(strict_types=1);

namespace Modules\Ordering\Domain;

use Modules\Ordering\Contracts\Data\OrderStatus;

/**
 * Bảng chuyển trạng thái đơn (docs/09-order/order.md §3). Cố định — không mở rộng bằng plugin.
 */
final class OrderStateMachine
{
    private const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['processing', 'cancelled'],
        'processing' => ['completed', 'cancelled'],
        'completed' => [],
        'cancelled' => [],
    ];

    public static function can(OrderStatus $from, OrderStatus $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$from->value], true);
    }

    /** Thứ tự tiến của đơn (đã huỷ không nằm trong thứ tự). */
    private const RANK = ['pending' => 0, 'confirmed' => 1, 'processing' => 2, 'completed' => 3];

    /**
     * Nhảy tới trước ngoài trình tự (0.3.99, nhân viên có quyền + lý do): từ trạng thái chưa kết thúc tới trạng thái sau nó
     * (vd. chờ xác nhận → hoàn tất). Không lùi, không mở lại đơn đã hoàn tất/huỷ; huỷ luôn theo đường huỷ thường.
     */
    public static function canForce(OrderStatus $from, OrderStatus $to): bool
    {
        if (in_array($from, [OrderStatus::Completed, OrderStatus::Cancelled], true) || $to === OrderStatus::Cancelled) {
            return false;
        }

        return self::RANK[$to->value] > self::RANK[$from->value];
    }

    /**
     * @return list<OrderStatus>
     */
    public static function next(OrderStatus $from): array
    {
        return array_map(fn (string $status): OrderStatus => OrderStatus::from($status), self::TRANSITIONS[$from->value]);
    }
}
