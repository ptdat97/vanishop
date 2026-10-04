<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Integration\Application\CanonicalPayloads;
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
        private readonly CanonicalPayloads $payloads,
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
        $this->forOrder($event->orderId, 'order.created');
    }

    public function orderConfirmed(OrderConfirmed $event): void
    {
        $this->forOrder($event->orderId, 'order.confirmed', ['reason' => $event->reason]);
    }

    public function orderCancelled(OrderCancelled $event): void
    {
        $this->forOrder($event->orderId, 'order.cancelled', ['reason' => $event->reason, 'source' => $event->source]);
    }

    public function orderLinesCancelled(OrderLinesCancelled $event): void
    {
        $this->forOrder($event->orderId, 'order.lines_cancelled', [
            'cancellation_id' => $event->cancellationId, 'reason' => $event->reason, 'amount' => $event->amount,
            'lines' => array_map(fn (array $line): array => ['line_id' => $line['order_line_id'], 'variant_id' => $line['variant_id'], 'quantity' => $line['quantity'], 'amount' => $line['amount']], $event->lines),
        ]);
    }

    public function paymentCaptured(PaymentCaptured $event): void
    {
        $this->related($event->orderId, 'payment.captured', fn (OrderData $order): array => [
            'payment_id' => $event->paymentPublicId ?? (string) $event->paymentId, 'gateway' => $event->gatewayCode, 'amount' => $event->amount, 'currency' => $order->currencyCode,
        ]);
    }

    public function refundCompleted(RefundCompleted $event): void
    {
        $this->related($event->orderId, 'payment.refunded', fn (OrderData $order): array => [
            'refund_id' => $event->refundPublicId ?? (string) $event->refundId, 'payment_id' => $event->paymentPublicId ?? (string) $event->paymentId, 'amount' => $event->amount, 'currency' => $order->currencyCode,
        ]);
    }

    public function returnRequested(ReturnRequested $event): void
    {
        $this->related($event->orderId, 'return.created', fn (): array => [
            'return_id' => $event->publicId ?? (string) $event->returnId, 'return_number' => $event->number, 'source' => $event->source,
        ]);
    }

    public function returnResolved(ReturnResolved $event): void
    {
        $this->related($event->orderId, 'return.resolved', fn (OrderData $order): array => [
            'return_id' => $event->publicId ?? (string) $event->returnId, 'return_number' => $event->number, 'refunded_amount' => $event->refundedAmount, 'currency' => $order->currencyCode,
        ]);
    }

    public function shipmentStatusChanged(ShipmentStatusChanged $event): void
    {
        $this->related($event->orderId, 'shipment.status_changed', fn (): array => [
            'shipment_id' => $event->publicId ?? (string) $event->shipmentId, 'carrier' => $event->carrierCode, 'tracking_number' => $event->trackingNumber, 'from' => $event->from, 'to' => $event->to,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function forOrder(int $orderId, string $type, array $extra = []): void
    {
        $this->publish($orderId, $type, fn (OrderData $order): array => ['order' => $this->payloads->order($order), ...$extra]);
    }

    /**
     * @param  callable(OrderData): array<string, mixed>  $data
     */
    private function related(int $orderId, string $type, callable $data): void
    {
        $this->publish($orderId, $type, fn (OrderData $order): array => ['order_number' => $order->number, ...$data($order)]);
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
