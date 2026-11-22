<?php

declare(strict_types=1);

namespace Modules\Ordering\Domain;

/**
 * Mã hàng đợi Admin. Cột sinh `orders.stage` tính từ trạng thái đơn, thanh toán, giao hàng, sự cố và đổi trả.
 */
final class OrderStage
{
    public const STAGES = ['to_confirm', 'awaiting_payment', 'to_ship', 'shipping', 'issue', 'returns', 'delivered', 'completed', 'cancelled'];

    /** Hàng đợi cần nhân viên làm — hiện số đếm. */
    public const ACTIONABLE = ['to_confirm', 'awaiting_payment', 'to_ship', 'issue', 'returns'];
}
