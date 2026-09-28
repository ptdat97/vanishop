<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Domain;

/**
 * Trạng thái vận đơn chuẩn hoá (docs/09-order/fulfillment.md §2). Chỉ đi tiến theo `rank`
 * (webhook đến trễ/sai thứ tự không làm lùi trạng thái); `failed_attempt` ↔ `out_for_delivery` được lặp.
 */
enum ShipmentStatus: string
{
    case PendingBooking = 'pending_booking';
    case BookingFailed = 'booking_failed';
    case Created = 'created';
    case PickedUp = 'picked_up';
    case InTransit = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case FailedAttempt = 'failed_attempt';
    case Delivered = 'delivered';
    case Returning = 'returning';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    public function rank(): int
    {
        return match ($this) {
            self::PendingBooking, self::BookingFailed => 0,
            self::Created => 1,
            self::PickedUp => 2,
            self::InTransit => 3,
            self::OutForDelivery, self::FailedAttempt => 4,
            self::Returning => 5,
            self::Delivered, self::Returned, self::Cancelled => 9,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Returned, self::Cancelled], true);
    }

    /** Hàng đã rời kho. */
    public function hasLeftWarehouse(): bool
    {
        return $this->rank() >= 2 && $this !== self::Cancelled;
    }

    public function canMoveTo(self $to): bool
    {
        if ($this->isTerminal() || $to === $this) {
            return false;
        }
        if ($to === self::Cancelled) {
            return ! $this->hasLeftWarehouse();
        }
        if ($to === self::Delivered) {
            return $this->rank() >= 1 && $this !== self::Returning;
        }
        if (in_array($to, [self::Returning, self::Returned], true)) {
            return $this->hasLeftWarehouse();
        }
        if ($to === self::BookingFailed) {
            return $this === self::PendingBooking;
        }

        return $to->rank() > $this->rank() || ($to->rank() === $this->rank() && $to->rank() === 4);
    }
}
