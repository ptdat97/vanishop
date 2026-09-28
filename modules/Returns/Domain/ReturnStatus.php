<?php

declare(strict_types=1);

namespace Modules\Returns\Domain;

/**
 * Trạng thái yêu cầu đổi/trả (docs/09-order/order.md §7). "inspected" gộp vào "received": nhân viên ghi
 * tình trạng từng dòng ngay khi nhận hàng.
 */
enum ReturnStatus: string
{
    case Requested = 'requested';
    case Approved = 'approved';
    case InTransit = 'in_transit';
    case Received = 'received';
    case Resolved = 'resolved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    private const TRANSITIONS = [
        'requested' => ['approved', 'rejected', 'cancelled'],
        'approved' => ['in_transit', 'received', 'cancelled'],
        'in_transit' => ['received'],
        'received' => ['resolved', 'rejected'],
        'resolved' => [],
        'rejected' => [],
        'cancelled' => [],
    ];

    public function canMoveTo(self $to): bool
    {
        return in_array($to->value, self::TRANSITIONS[$this->value], true);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Requested, self::Approved, self::InTransit, self::Received], true);
    }

    /** Số lượng của yêu cầu còn "chiếm chỗ" trong giới hạn trả của dòng. */
    public function countsTowardsLimit(): bool
    {
        return $this->isOpen() || $this === self::Resolved;
    }
}
