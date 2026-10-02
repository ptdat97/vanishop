<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Fulfillment\Contracts\Data\ShipmentView;
use Modules\Fulfillment\Contracts\ShipmentReader;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Returns\Contracts\Data\ReturnView;
use Modules\Returns\Contracts\Returns;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\MoneyFormatter;

final class OrderPresenter
{
    public function __construct(
        private readonly MoneyFormatter $money,
        private readonly ShipmentReader $shipments,
        private readonly Returns $returns,
        private readonly Enrichment $enrichment,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(OrderDetail $order): array
    {
        $money = fn (?int $amount): ?array => $amount === null ? null : $this->money->toArray(Money::of($amount, $order->currencyCode));

        return $this->enrichment->applyOne('order', [
            'id' => $order->publicId,
            'number' => $order->number,
            'status' => $order->customerStatus,
            'order_status' => $order->orderStatus,
            'payment_status' => $order->paymentStatus,
            'fulfillment_status' => $order->fulfillmentStatus,
            'payment_method' => $order->paymentMethod,
            'placed_at' => $order->placedAt,
            'can_cancel' => $order->cancellableByCustomer,
            'customer' => $order->customer,
            'shipping_address' => $order->shippingAddress,
            'shipping_method' => ['label' => $order->shippingMethod['label'] ?? null, 'fee' => $money((int) ($order->shippingMethod['fee'] ?? 0))],
            'lines' => array_map(fn (array $line): array => [
                'id' => $line['id'], 'sku' => $line['sku'], 'name' => $line['name'], 'color_name' => $line['color_name'], 'size_code' => $line['size_code'], 'image_url' => $line['image_url'],
                'quantity' => $line['quantity'], 'unit_price' => $money($line['unit_amount']), 'compare_at' => $money($line['compare_at_amount']),
                'discount' => $money($line['discount_amount']), 'total' => $money($line['total_amount']),
                'options' => $line['options'] ?? [],
            ], $order->lines),
            'adjustments' => array_map(fn (array $adjustment): array => ['type' => $adjustment['type'], 'code' => $adjustment['code'], 'label' => $adjustment['label'], 'amount' => $money($adjustment['amount'])], $order->adjustments),
            'subtotal' => $money($order->amounts['subtotal']),
            'discount' => $money($order->amounts['discount']),
            'shipping_fee' => $money($order->amounts['shipping']),
            'tax_included' => $money($order->amounts['tax']),
            'total' => $money($order->amounts['total']),
            'timeline' => array_map(fn (array $event): array => ['type' => $event['type'], 'to' => $event['to'], 'at' => $event['at']], $order->events),
            'shipments' => array_values(array_map(fn (ShipmentView $shipment): array => [
                'carrier' => $shipment->carrierLabel,
                'service' => $shipment->serviceCode,
                'tracking_number' => $shipment->trackingNumber,
                'status' => $shipment->status,
                'events' => $shipment->events,
            ], array_filter($this->shipments->forOrder($order->id), fn (ShipmentView $shipment): bool => $shipment->status !== 'cancelled'))),
            'returns' => array_map(fn (ReturnView $return): array => [
                'id' => $return->publicId, 'number' => $return->number, 'status' => $return->status, 'reason_code' => $return->reasonCode,
                'refund' => $money($return->refundedAmount ?? $return->refundAmount), 'created_at' => $return->createdAt,
                'lines' => array_map(fn (array $line): array => ['order_line_id' => $line['order_line_id'], 'name' => $line['name'], 'quantity' => $line['quantity']], $return->lines),
            ], $this->returns->forOrder($order->id)),
            'returnable' => $this->returns->returnable($order->id),
        ]);
    }
}
