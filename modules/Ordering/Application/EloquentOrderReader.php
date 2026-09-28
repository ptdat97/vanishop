<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Persistence\Models\Order;

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

    private function toData(Order $order): OrderData
    {
        return new OrderData(
            id: $order->id, publicId: $order->public_id, number: $order->number, legalEntityId: (int) $order->legal_entity_id,
            brandId: $order->brand_id, channelId: $order->channel_id, customerId: $order->customer_id === null ? null : (int) $order->customer_id,
            status: $order->order_status, paymentStatus: $order->payment_status, paymentMethod: (string) $order->payment_method,
            totalAmount: $order->total_amount, currencyCode: $order->currency_code, reservationKey: (string) $order->reservation_key,
        );
    }
}
