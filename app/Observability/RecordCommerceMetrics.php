<?php

declare(strict_types=1);

namespace App\Observability;

use Modules\Fulfillment\Events\ShipmentStatusChanged;
use Modules\Ordering\Events\OrderCancelled;
use Modules\Ordering\Events\OrderLinesCancelled;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Payment\Events\PaymentCaptured;
use Modules\Payment\Events\PaymentFailed;
use Modules\Payment\Events\RefundCompleted;
use Modules\Shared\Contracts\Metrics;

/**
 * Counter từ domain event (sau commit): đếm cái đã xảy ra thật, không đếm request.
 */
final class RecordCommerceMetrics
{
    public function __construct(private readonly Metrics $metrics) {}

    /**
     * @return array<class-string, string>
     */
    public static function listeners(): array
    {
        return [
            OrderPlaced::class => 'orderPlaced',
            OrderCancelled::class => 'orderCancelled',
            OrderLinesCancelled::class => 'orderLinesCancelled',
            PaymentCaptured::class => 'paymentCaptured',
            PaymentFailed::class => 'paymentFailed',
            RefundCompleted::class => 'refundCompleted',
            ShipmentStatusChanged::class => 'shipmentStatusChanged',
        ];
    }

    public function orderPlaced(OrderPlaced $event): void
    {
        $this->metrics->increment('orders.created');
    }

    public function orderCancelled(OrderCancelled $event): void
    {
        $this->metrics->increment('orders.cancelled', 1, $event->source);
    }

    public function orderLinesCancelled(OrderLinesCancelled $event): void
    {
        $this->metrics->increment('orders.lines_cancelled');
    }

    public function paymentCaptured(PaymentCaptured $event): void
    {
        $this->metrics->increment('payments.captured', 1, $event->gatewayCode);
    }

    public function paymentFailed(PaymentFailed $event): void
    {
        $this->metrics->increment('payments.failed', 1, $event->gatewayCode);
    }

    public function refundCompleted(RefundCompleted $event): void
    {
        $this->metrics->increment('payments.refunded');
    }

    public function shipmentStatusChanged(ShipmentStatusChanged $event): void
    {
        if (in_array($event->to, ['delivered', 'returned', 'failed_attempt'], true)) {
            $this->metrics->increment('shipments.'.$event->to, 1, $event->carrierCode ?? 'all');
        }
    }
}
