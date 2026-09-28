<?php

declare(strict_types=1);

namespace Modules\Payment\Application\Listeners;

use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Events\OrderPlaced;

/**
 * COD: xác nhận đơn tự động sau khi đặt (tắt bằng VANI_COD_AUTO_CONFIRM=false để CSKH gọi xác nhận trước).
 */
final class AutoConfirmCodOrder
{
    public function __construct(
        private readonly OrderReader $orders,
        private readonly OrderTransitions $transitions,
    ) {}

    public function handle(OrderPlaced $event): void
    {
        if (! config('vanishop.payment.cod.auto_confirm', true)) {
            return;
        }

        $order = $this->orders->find($event->orderId);
        if ($order !== null && $order->paymentMethod === 'cod' && $order->status === OrderStatus::Pending) {
            $this->transitions->transition($order->id, OrderStatus::Confirmed, 'cod_auto_confirm', 'system');
        }
    }
}
