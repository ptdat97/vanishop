<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application\Listeners;

use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Inventory\Contracts\InventoryReservation;
use Modules\Ordering\Events\OrderStatusOverridden;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Đơn bị đưa thẳng tới "Hoàn tất" ngoài trình tự (0.3.99): hàng coi như đã giao ngoài hệ thống — huỷ vận đơn còn chưa rời
 * kho và chốt (trừ tồn) hàng đang giữ, để tồn kho khớp thực tế và không còn vận đơn treo trong hàng đợi giao hãng.
 */
final class SettleOrderOnStatusOverride
{
    public function __construct(
        private readonly FulfillmentService $fulfillment,
        private readonly InventoryReservation $inventory,
        private readonly CurrentContext $context,
    ) {}

    public function handle(OrderStatusOverridden $event): void
    {
        if ($event->to !== 'completed') {
            return;
        }

        $this->context->runAs(ContextScope::system('order completed out of sequence'), function () use ($event): void {
            $this->fulfillment->cancelOpenShipments($event->orderId, 'order_completed_override');
            if ($event->reservationKey !== '') {
                $this->inventory->commit($event->reservationKey);
            }
        });
    }
}
