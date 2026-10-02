<?php

declare(strict_types=1);

namespace Plugin\Cod\Listeners;

use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Tenancy\Contracts\Settings;
use Plugin\Cod\CodServiceProvider;

/**
 * Xác nhận đơn COD tự động sau khi đặt (tắt bằng cấu hình `auto_confirm` để CSKH gọi xác nhận trước).
 */
final class AutoConfirmCodOrder
{
    public function __construct(
        private readonly OrderReader $orders,
        private readonly OrderTransitions $transitions,
        private readonly Settings $settings,
    ) {}

    public function handle(OrderPlaced $event): void
    {
        if (! (bool) $this->settings->get(CodServiceProvider::ID, 'auto_confirm', config('vani.cod.auto_confirm', true))) {
            return;
        }

        $order = $this->orders->find($event->orderId);
        if ($order !== null && $order->paymentStatus === 'cod_pending' && $order->status === OrderStatus::Pending) {
            $this->transitions->transition($order->id, OrderStatus::Confirmed, 'cod_auto_confirm', 'system');
        }
    }
}
