<?php

declare(strict_types=1);

namespace Modules\Ordering\Domain;

/**
 * Giai đoạn vận hành của đơn cho nhân viên (0.3.96) — TÍNH từ 4 chiều trạng thái (đơn, thanh toán, giao hàng, đổi trả),
 * không lưu, không đổi bảng chuyển trạng thái. Là các hàng đợi việc ở danh sách đơn Admin. Một biểu thức SQL duy nhất
 * ({@see self::sql()}) dùng cho lọc, đếm và hiển thị nên không lệch nhau. Thứ tự ưu tiên: huỷ → đổi/trả đang mở → hoàn tất
 * → hoàn về → chờ (thanh toán | xác nhận) → đã giao → đang giao → chờ giao hãng (đã xác nhận, vận đơn chưa rời kho).
 */
final class OrderStage
{
    public const STAGES = ['to_confirm', 'awaiting_payment', 'to_ship', 'shipping', 'issue', 'returns', 'delivered', 'completed', 'cancelled'];

    /** Hàng đợi cần nhân viên làm — hiện số đếm. */
    public const ACTIONABLE = ['to_confirm', 'awaiting_payment', 'to_ship', 'issue', 'returns'];

    public static function sql(): string
    {
        return "CASE
            WHEN order_status = 'cancelled' THEN 'cancelled'
            WHEN return_status IN ('requested', 'in_progress') THEN 'returns'
            WHEN order_status = 'completed' THEN 'completed'
            WHEN fulfillment_status = 'returned_to_sender' THEN 'issue'
            WHEN order_status = 'pending' AND payment_status IN ('unpaid', 'failed') THEN 'awaiting_payment'
            WHEN order_status = 'pending' THEN 'to_confirm'
            WHEN fulfillment_status = 'delivered' THEN 'delivered'
            WHEN fulfillment_status IN ('shipped', 'partially_shipped') THEN 'shipping'
            ELSE 'to_ship'
        END";
    }
}
