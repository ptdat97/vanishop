<?php

declare(strict_types=1);

namespace Modules\Notification\Application\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Brand\Contracts\BrandDirectory;
use Modules\Fulfillment\Contracts\ShipmentReader;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Notification\Contracts\Data\NotificationRequest;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Notifier;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\MoneyFormatter;
use Throwable;

/**
 * Tin giao dịch theo domain event (không cần consent marketing). Gửi tới liên hệ trên snapshot đơn.
 * Lỗi xếp hàng tin không làm hỏng nghiệp vụ.
 */
final class SendOrderNotifications
{
    public function __construct(
        private readonly Notifier $notifier,
        private readonly OrderReader $orders,
        private readonly ShipmentReader $shipments,
        private readonly BrandDirectory $brands,
        private readonly MoneyFormatter $money,
        private readonly CurrentContext $context,
    ) {}

    public function orderPlaced(OrderPlaced $event): void
    {
        $this->send($event->orderId, 'order_placed', "order_placed:{$event->orderId}");
    }

    public function orderCancelled(OrderCancelled $event): void
    {
        $this->send($event->orderId, 'order_cancelled', "order_cancelled:{$event->orderId}");
    }

    public function shipmentStatusChanged(ShipmentStatusChanged $event): void
    {
        $type = match ($event->to) {
            'picked_up', 'in_transit' => 'shipment_shipped',
            'delivered' => 'shipment_delivered',
            default => null,
        };
        if ($type === null) {
            return;
        }

        $this->send($event->orderId, $type, "{$type}:{$event->shipmentId}", function (OrderData $order) use ($event): array {
            foreach ($this->shipments->forOrder($order->id) as $shipment) {
                if ($shipment->id === $event->shipmentId) {
                    return ['carrier' => $shipment->carrierLabel, 'tracking_number' => (string) $shipment->trackingNumber];
                }
            }

            return [];
        });
    }

    /**
     * @param  (callable(OrderData): array<string, string>)|null  $extra
     */
    private function send(int $orderId, string $type, string $key, ?callable $extra = null): void
    {
        try {
            $this->context->runAs(ContextScope::system("notification {$type}"), function () use ($orderId, $type, $key, $extra): void {
                $order = $this->orders->find($orderId);
                if ($order === null) {
                    return;
                }

                $recipient = new Recipient(
                    phone: $order->recipient['phone'] !== '' ? $order->recipient['phone'] : null,
                    email: $order->recipient['email'] ?? null,
                    name: $order->recipient['full_name'],
                    customerId: $order->customerId,
                );
                $variables = [
                    'brand_name' => $this->brands->find($order->brandId)->name ?? '',
                    'customer_name' => $order->recipient['full_name'],
                    'order_number' => $order->number,
                    'total' => $this->money->format(Money::of($order->totalAmount, $order->currencyCode)),
                    ...($extra === null ? [] : $extra($order)),
                ];

                $this->notifier->notify(new NotificationRequest($type, $key, $order->brandId, $recipient, $variables));
            });
        } catch (Throwable $exception) {
            report($exception);
            Log::error('Không xếp hàng được thông báo.', ['type' => $type, 'order_id' => $orderId, 'error' => $exception->getMessage()]);
        }
    }
}
