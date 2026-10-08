<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Modules\Ordering\Contracts\Data\OrderData;

/**
 * Dữ liệu (`data`) của event tích hợp — một nơi dựng cho cả luồng phát trực tiếp (PublishDomainEvents) và đối soát bù
 * (OrderEventReconciler), để event bù có cùng hình dạng với event gốc (schema docs/api/schemas/events/*).
 */
final class DomainEventPayloads
{
    public function __construct(private readonly CanonicalPayloads $canonical) {}

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function order(OrderData $order, array $extra = []): array
    {
        return ['order' => $this->canonical->order($order), ...$extra];
    }

    /**
     * @param  list<array{order_line_id: int, variant_id: int, quantity: int, amount: int}>  $lines
     * @return array<string, mixed>
     */
    public function linesCancelled(OrderData $order, string $cancellationId, string $reason, int $amount, array $lines, int $promotionClawback = 0): array
    {
        return $this->order($order, [
            'cancellation_id' => $cancellationId, 'reason' => $reason, 'amount' => $amount, 'promotion_clawback' => $promotionClawback,
            'lines' => array_map(fn (array $line): array => ['line_id' => $line['order_line_id'], 'variant_id' => $line['variant_id'], 'quantity' => $line['quantity'], 'amount' => $line['amount']], $lines),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentCaptured(OrderData $order, string $paymentId, string $gateway, int $amount): array
    {
        return ['order_number' => $order->number, 'payment_id' => $paymentId, 'gateway' => $gateway, 'amount' => $amount, 'currency' => $order->currencyCode];
    }

    /**
     * @return array<string, mixed>
     */
    public function paymentRefunded(OrderData $order, string $refundId, string $paymentId, int $amount): array
    {
        return ['order_number' => $order->number, 'refund_id' => $refundId, 'payment_id' => $paymentId, 'amount' => $amount, 'currency' => $order->currencyCode];
    }

    /**
     * @return array<string, mixed>
     */
    public function returnCreated(OrderData $order, string $returnId, string $number, string $source): array
    {
        return ['order_number' => $order->number, 'return_id' => $returnId, 'return_number' => $number, 'source' => $source];
    }

    /**
     * @return array<string, mixed>
     */
    public function returnResolved(OrderData $order, string $returnId, ?string $number, int $refundedAmount): array
    {
        return ['order_number' => $order->number, 'return_id' => $returnId, 'return_number' => $number, 'refunded_amount' => $refundedAmount, 'currency' => $order->currencyCode];
    }

    /**
     * @return array<string, mixed>
     */
    public function shipmentStatusChanged(OrderData $order, string $shipmentId, ?string $carrier, ?string $trackingNumber, string $from, string $to): array
    {
        return ['order_number' => $order->number, 'shipment_id' => $shipmentId, 'carrier' => $carrier, 'tracking_number' => $trackingNumber, 'from' => $from, 'to' => $to];
    }
}
