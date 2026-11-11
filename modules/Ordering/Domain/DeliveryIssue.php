<?php

declare(strict_types=1);

namespace Modules\Ordering\Domain;

/**
 * Giá trị hợp lệ của orders.delivery_issue (sự cố giao hàng đang mở, tổng hợp từ vận đơn, do Fulfillment cập nhật).
 * null = không có sự cố. Đơn có sự cố nằm ở hàng đợi `issue` ({@see OrderStage}) cho tới khi nhân viên xử lý xong.
 */
final class DeliveryIssue
{
    /** Hãng không nhận đặt vận đơn sau số lần thử tối đa — đặt lại hoặc chuyển nhập tay. */
    public const BOOKING_FAILED = 'booking_failed';

    /** Giao không thành công — gọi khách, hẹn giao lại. */
    public const FAILED_ATTEMPT = 'failed_attempt';

    /** Hàng đang hoàn về kho. */
    public const RETURNING = 'returning';

    public const VALUES = [self::BOOKING_FAILED, self::FAILED_ATTEMPT, self::RETURNING];
}
