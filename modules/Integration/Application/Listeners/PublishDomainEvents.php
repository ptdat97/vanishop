<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Integration\Application\DomainEventPayloads;
use Modules\Integration\Contracts\Data\IntegrationEvent;
use Modules\Integration\Contracts\IntegrationEvents;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderConfirmed;
use Modules\Ordering\Events\OrderLinesCancelled;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Payment\Events\PaymentCaptured;
use Modules\Payment\Events\RefundCompleted;
use Modules\Returns\Events\ReturnRequested;
use Modules\Returns\Events\ReturnResolved;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Throwable;

/**
 * Domain event (dispatch sau commit) → event tích hợp. Mọi event đều có aggregate = đơn hàng (số đơn), nên
 * đối tác nhận theo đúng thứ tự: order.created trước payment.captured, trước shipment…
 *
 * Domain event của Core là `ShouldDispatchAfterCommit`, nên việc ghi feed/outbox chạy ngay sau commit nghiệp vụ
 * (không cùng transaction). Nếu tiến trình chết đúng giữa hai bước thì mất event; đối soát đơn hàng
 * (integration-platform §8) phát hiện và gửi lại. Lỗi ghi outbox không làm hỏng request của khách.
 */
final class PublishDomainEvents
{
    public function __construct(
        private readonly IntegrationEvents $events,
        private readonly OrderReader $orders,
        private readonly DomainEventPayloads $payloads,
        private readonly CurrentContext $context,
    ) {}

    /**
     * Domain event → method xử lý. Đăng ký dạng [class, method] để listener chỉ được resolve khi có event.
     *
     * @return array<class-string, string>
     */
    public static function listeners(): array
    {
        return [
            OrderPlaced::class => 'orderPlaced',
            OrderConfirmed::class => 'orderConfirmed',
            OrderCancelled::class => 'orderCancelled',
            OrderLinesCancelled::class => 'orderLinesCancelled',
            PaymentCaptured::class => 'paymentCaptured',
            RefundCompleted::class => 'refundCompleted',
            ReturnRequested::class => 'returnRequested',
            ReturnResolved::class => 'returnResolved',
            ShipmentStatusChanged::class => 'shipmentStatusChanged',
        ];
    }

    public function orderPlaced(OrderPlaced $event): void
    {
        $this->publish($event->orderId, 'order.created', fn (OrderData $order): array => $this->payloads->order($order));
    }

    public function orderConfirmed(OrderConfirmed $event): void
    {
        $this->publish($event->orderId, 'order.confirmed', fn (OrderData $order): array => $this->payloads->order($order, ['reason' => $event->reason]));
    }

    public function orderCancelled(OrderCancelled $event): void
    {
        $this->publish($event->orderId, 'order.cancelled', fn (OrderData $order): array => $this->payloads->order($order, ['reason' => $event->reason, 'source' => $event->source]));
    }

    public function orderLinesCancelled(OrderLinesCancelled $event): void
    {
        $this->publish($event->orderId, 'order.lines_cancelled', fn (OrderData $order): array => $this->payloads->linesCancelled($order, $event->cancellationId, $event->reason, $event->amount, $event->lines));
    }

    public function paymentCaptured(PaymentCaptured $event): void
    {
        $this->publish($event->orderId, 'payment.captured', fn (OrderData $order): array => $this->payloads->paymentCaptured($order, $event->paymentPublicId ?? (string) $event->paymentId, $event->gatewayCode, $event->amount));
    }

    public function refundCompleted(RefundCompleted $event): void
    {
        $this->publish($event->orderId, 'payment.refunded', fn (OrderData $order): array => $this->payloads->paymentRefunded($order, $event->refundPublicId ?? (string) $event->refundId, $event->paymentPublicId ?? (string) $event->paymentId, $event->amount));
    }

    public function returnRequested(ReturnRequested $event): void
    {
        $this->publish($event->orderId, 'return.created', fn (OrderData $order): array => $this->payloads->returnCreated($order, $event->publicId ?? (string) $event->returnId, $event->number, $event->source));
    }

    public function returnResolved(ReturnResolved $event): void
    {
        $this->publish($event->orderId, 'return.resolved', fn (OrderData $order): array => $this->payloads->returnResolved($order, $event->publicId ?? (string) $event->returnId, $event->number, $event->refundedAmount));
    }

    public function shipmentStatusChanged(ShipmentStatusChanged $event): void
    {
        $this->publish($event->orderId, 'shipment.status_changed', fn (OrderData $order): array => $this->payloads->shipmentStatusChanged($order, $event->publicId ?? (string) $event->shipmentId, $event->carrierCode, $event->trackingNumber, $event->from, $event->to));
    }

    /**
     * @param  callable(OrderData): array<string, mixed>  $data
     */
    private function publish(int $orderId, string $type, callable $data): void
    {
        try {
            $this->context->runAs(ContextScope::system("integration {$type}"), function () use ($orderId, $type, $data): void {
                $order = $this->orders->find($orderId);
                if ($order === null) {
                    return;
                }

                $this->events->publish(new IntegrationEvent($type, 'order', $order->number, $data($order)));
            });
        } catch (Throwable $exception) {
            report($exception);
            Log::critical('Không ghi được event tích hợp.', ['type' => $type, 'order_id' => $orderId, 'error' => $exception->getMessage()]);
        }
    }
}
