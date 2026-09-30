<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use DateTimeInterface;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\Data\OrderLineData;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Ordering\Persistence\Models\OrderLine;

final class EloquentOrderReader implements OrderReader
{
    public function find(int $orderId): ?OrderData
    {
        $order = Order::query()->find($orderId);

        return $order === null ? null : $this->toData($order);
    }

    public function findByPublicId(string $publicId): ?OrderData
    {
        $order = Order::query()->where('public_id', $publicId)->first();

        return $order === null ? null : $this->toData($order);
    }

    public function findByNumber(string $number): ?OrderData
    {
        $order = Order::query()->where('number', strtoupper(trim($number)))->first();

        return $order === null ? null : $this->toData($order);
    }

    public function lines(int $orderId): array
    {
        if (! Order::query()->whereKey($orderId)->exists()) {
            return [];
        }

        return OrderLine::query()->where('order_id', $orderId)->orderBy('id')->get()
            ->map(fn (OrderLine $line): OrderLineData => new OrderLineData($line->id, $line->variant_id, $line->sku, $line->product_name, $line->quantity, $line->total_amount, $line->color_name, (string) $line->size_code))
            ->all();
    }

    public function customerHasPlacedOrder(int $customerId, ?int $brandId = null): bool
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->when($brandId !== null, fn ($query) => $query->where('brand_id', $brandId))
            ->where('order_status', '!=', OrderStatus::Cancelled->value)
            ->exists();
    }

    public function changedSince(?DateTimeInterface $since, ?int $afterId, int $limit): array
    {
        return Order::query()
            ->when($since !== null, fn ($query) => $query->where(fn ($query) => $query
                ->where('updated_at', '>', $since)
                ->orWhere(fn ($query) => $query->where('updated_at', $since)->where('id', '>', $afterId ?? 0))))
            ->orderBy('updated_at')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (Order $order): OrderData => $this->toData($order))
            ->all();
    }

    private function toData(Order $order): OrderData
    {
        return new OrderData(
            id: $order->id, publicId: $order->public_id, number: $order->number, legalEntityId: (int) $order->legal_entity_id,
            brandId: $order->brand_id, channelId: $order->channel_id, customerId: $order->customer_id === null ? null : (int) $order->customer_id,
            status: $order->order_status, paymentStatus: $order->payment_status, paymentMethod: (string) $order->payment_method,
            totalAmount: $order->total_amount, currencyCode: $order->currency_code, reservationKey: (string) $order->reservation_key,
            returnStatus: (string) $order->return_status,
            recipient: ['full_name' => (string) ($order->customer_snapshot['full_name'] ?? ''), 'phone' => (string) ($order->customer_snapshot['phone'] ?? '')],
            shippingAddress: array_map('strval', (array) $order->shipping_address),
            fulfillmentStatus: (string) $order->fulfillment_status,
            placedAt: $order->placed_at?->toIso8601String(),
            updatedAt: $order->updated_at?->toIso8601String(),
        );
    }
}
