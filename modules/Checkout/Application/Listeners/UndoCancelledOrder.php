<?php

declare(strict_types=1);

namespace Modules\Checkout\Application\Listeners;

use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Promotion\Contracts\PromotionEngine;

/**
 * Đơn huỷ → nhả giữ hàng và hoàn lượt khuyến mãi. Cả hai thao tác idempotent (event có thể đến lại).
 * Checkout điều phối vì Checkout là nơi đã giữ hàng và ghi lượt khi đặt.
 */
final class UndoCancelledOrder
{
    public function __construct(
        private readonly InventoryReservation $inventory,
        private readonly PromotionEngine $promotions,
    ) {}

    public function handle(OrderCancelled $event): void
    {
        $this->inventory->release($event->reservationKey, "order_cancelled:{$event->reason}");
        $this->promotions->revertUsage($event->orderId);
    }
}
