<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use DateTimeInterface;
use Illuminate\Support\Facades\DB;
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
            ->map(fn (OrderLine $line): OrderLineData => new OrderLineData($line->id, $line->variant_id, $line->sku, $line->product_name, $line->quantity, $line->total_amount, $line->color_name, (string) $line->size_code, $line->brand_name, (array) ($line->meta['options'] ?? [])))
            ->all();
    }

    public function customerHasPlacedOrder(int $customerId): bool
    {
        return Order::query()
            ->where('customer_id', $customerId)
            ->where('order_status', '!=', OrderStatus::Cancelled->value)
            ->exists();
    }

    public function customerStats(int $customerId): array
    {
        $row = Order::query()
            ->where('customer_id', $customerId)
            ->where('order_status', '!=', OrderStatus::Cancelled->value)
            ->selectRaw('count(*) as orders_count, coalesce(sum(total_amount), 0) as total_spent, min(placed_at) as first_order_at, max(placed_at) as last_order_at')
            ->toBase()
            ->first();

        return [
            'orders_count' => (int) ($row->orders_count ?? 0), 'total_spent' => (int) ($row->total_spent ?? 0),
            'first_order_at' => ($row->first_order_at ?? null) === null ? null : (string) $row->first_order_at,
            'last_order_at' => ($row->last_order_at ?? null) === null ? null : (string) $row->last_order_at,
        ];
    }

    public function cancellations(int $orderId): array
    {
        $variants = OrderLine::query()->where('order_id', $orderId)->pluck('variant_id', 'id');

        return DB::table('order_events')->where('order_id', $orderId)->where('type', 'lines_cancelled')->orderBy('id')->get()
            ->map(function (object $event) use ($variants): array {
                $data = (array) json_decode((string) $event->data, true);

                return [
                    'cancellation_id' => (string) ($data['cancellation_id'] ?? ''),
                    'reason' => (string) $event->reason,
                    // Số tiền đơn giảm ròng (đã trừ khuyến mãi thu hồi, 0.3.31) — khớp OrderLinesCancelled::$amount.
                    'amount' => (int) ($data['totals']['net'] ?? $data['totals']['total'] ?? 0),
                    'promotion_clawback' => (int) ($data['totals']['promotion_clawback'] ?? 0),
                    'lines' => array_map(fn (array $line): array => [
                        'order_line_id' => (int) $line['order_line_id'], 'variant_id' => (int) ($variants[$line['order_line_id']] ?? 0),
                        'quantity' => (int) $line['quantity'], 'amount' => (int) $line['total'],
                    ], (array) ($data['lines'] ?? [])),
                ];
            })->all();
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
            id: $order->id, publicId: $order->public_id, number: $order->number, customerId: $order->customer_id === null ? null : (int) $order->customer_id,
            status: $order->order_status, paymentStatus: $order->payment_status, paymentMethod: (string) $order->payment_method,
            totalAmount: $order->total_amount, currencyCode: $order->currency_code, reservationKey: (string) $order->reservation_key,
            returnStatus: (string) $order->return_status,
            recipient: [
                'full_name' => (string) ($order->customer_snapshot['full_name'] ?? ''), 'phone' => (string) ($order->customer_snapshot['phone'] ?? ''),
                'email' => isset($order->customer_snapshot['email']) && $order->customer_snapshot['email'] !== '' ? (string) $order->customer_snapshot['email'] : null,
            ],
            shippingAddress: array_map('strval', (array) $order->shipping_address),
            fulfillmentStatus: (string) $order->fulfillment_status,
            placedAt: $order->placed_at->toIso8601String(),
            updatedAt: $order->updated_at?->toIso8601String(),
            meta: (array) ($order->meta ?? []),
            source: (string) $order->source,
            shippingMethod: (array) ($order->shipping_method ?? []),
        );
    }
}
