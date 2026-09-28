<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Console;

use Illuminate\Console\Command;
use Modules\Fulfillment\Domain\FulfillmentProgress;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Đơn đã giao hết và quá hạn đổi trả → completed (OrderCompleted: loyalty, hoá đơn điện tử… lắng nghe).
 */
final class CompleteDeliveredOrdersCommand extends Command
{
    protected $signature = 'vani:orders:complete-delivered';

    protected $description = 'Hoàn tất đơn đã giao quá hạn đổi trả';

    public function handle(CurrentContext $context, OrderReader $orders, OrderTransitions $transitions): int
    {
        $cutoff = now()->subDays((int) config('vanishop.fulfillment.return_window_days', 7));

        $completed = $context->runAs(ContextScope::system('complete delivered orders'), function () use ($cutoff, $orders, $transitions): int {
            $count = 0;
            $orderIds = Shipment::query()->where('status', ShipmentStatus::Delivered)->where('delivered_at', '<', $cutoff)->distinct()->pluck('order_id');

            foreach ($orderIds as $orderId) {
                $shipments = Shipment::query()->where('order_id', $orderId)->get();
                $order = $orders->find((int) $orderId);
                $allDelivered = FulfillmentProgress::orderStatus($shipments->pluck('status')->all()) === 'delivered';
                $latest = $shipments->where('status', ShipmentStatus::Delivered)->max('delivered_at');

                if ($order !== null && $order->status === OrderStatus::Processing && $allDelivered && $latest !== null && $latest < $cutoff) {
                    $count += $transitions->transition($order->id, OrderStatus::Completed, 'return_window_passed', 'system') ? 1 : 0;
                }
            }

            return $count;
        });

        $this->info("Đã hoàn tất {$completed} đơn.");

        return self::SUCCESS;
    }
}
