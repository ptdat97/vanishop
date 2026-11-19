<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

/**
 * Khoá cấu hình đơn hàng (namespace `core`, Admin → Cài đặt → Đơn hàng & đổi trả); mặc định theo `vanishop.orders.*` /
 * `vanishop.fulfillment.*` (.env).
 */
final class OrderSettings
{
    /** Tiền tố mã đơn (vd. VN → VN2611-00001). Đổi chỉ áp dụng cho đơn mới. */
    public const NUMBER_PREFIX = 'orders.number_prefix';

    /** Số ngày sau khi giao: khách còn được đổi trả; hết hạn thì đơn chuyển "Hoàn tất". */
    public const RETURN_WINDOW_DAYS = 'orders.return_window_days';

    /** Khách bấm "Đã nhận hàng" được khi đơn đang giao quá số ngày này (đã giao hết thì bấm được ngay). */
    public const CONFIRM_RECEIPT_AFTER_DAYS = 'orders.confirm_receipt_after_days';

    /** Đơn chưa xác nhận/chưa gửi quá số giờ này → "đơn tồn đọng" ở trang tổng quan. */
    public const AGED_HOURS = 'orders.aged_hours';
}
