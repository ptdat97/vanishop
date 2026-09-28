<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts;

use Modules\Ordering\Contracts\Data\OrderStatus;

/**
 * Cổng duy nhất đổi trạng thái đơn. Mỗi lần: khoá dòng orders, kiểm tra state machine, ghi order_events,
 * phát event sau commit. Chuyển sang trạng thái đang có → no-op (trả false).
 *
 * @param  string  $source  customer | staff | system | gateway:<code> | integration:<client> | carrier
 */
interface OrderTransitions
{
    /**
     * @throws OrderTransitionRejected
     */
    public function transition(int $orderId, OrderStatus $to, string $reason, string $source): bool;

    public function can(int $orderId, OrderStatus $to): bool;

    /**
     * Cập nhật chiều thanh toán (payment_status) — gọi bởi Payment. Ghi order_events.
     */
    public function setPaymentStatus(int $orderId, string $paymentStatus, string $reason, string $source): void;
}
