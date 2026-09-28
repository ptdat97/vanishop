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

    /**
     * @return list<OrderStatus>
     */
    public static function next(OrderStatus $from): array
    {
        return array_map(fn (string $status): OrderStatus => OrderStatus::from($status), self::TRANSITIONS[$from->value]);
    }
}
