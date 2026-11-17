<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Domain;

/**
 * Đặt vận đơn qua API hãng lỗi: thử lại đến `maxAttempts` lần; hết lượt khi vận đơn vẫn chờ đặt → `booking_failed` (nhân
 * viên đặt lại hoặc chuyển thủ công). Vận đơn đã đặt xong/bị huỷ giữa chừng thì thôi, không đánh lỗi.
 */
final class BookingRetry
{
    public static function exhausted(int $attempts, int $maxAttempts, ShipmentStatus $status): bool
    {
        return $attempts >= $maxAttempts && $status === ShipmentStatus::PendingBooking;
    }
}
