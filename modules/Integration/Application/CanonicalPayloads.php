<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Modules\Extension\Facades\Hook;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\Data\OrderLineData;
use Modules\Ordering\Contracts\Data\PriceBreakdown;
use Modules\Ordering\Contracts\OrderReader;

/**
 * Payload canonical có version cho đối tác. Chỉ THÊM field trong cùng version (R20).
 *
 * @see docs/11-integration/integration-platform.md §4
 */
final class CanonicalPayloads
{
    public const ORDER_SCHEMA = 'vanishop.order.v1';

    public function __construct(private readonly OrderReader $orders) {}

    /**
     * @return array<string, mixed>
     */
    public function order(OrderData $order): array
    {
        return $this->orders([$order])[0];
    }

    /**
     * Payload của nhiều đơn (một trang đồng bộ): dòng, tầng giá, số đơn gốc nạp theo lô — số truy vấn không tăng theo số đơn
     * (db-refactor §4.8). Giữ thứ tự đầu vào.
     *
     * @param  list<OrderData>  $orders
     * @return list<array<string, mixed>>
     */
    public function orders(array $orders): array
    {
        $ids = array_map(fn (OrderData $order): int => $order->id, $orders);
        $lines = $this->orders->linesOf($ids);
        $pricing = $this->orders->priceBreakdowns($ids);
        $parents = $this->orders->numbers(array_values(array_filter(array_map(fn (OrderData $order): ?int => $order->parentOrderId, $orders))));

        return array_map(function (OrderData $order) use ($lines, $pricing, $parents): array {
            $payload = $this->canonical($order, $lines[$order->id] ?? [], $pricing[$order->id] ?? null, $order->parentOrderId === null ? null : ($parents[$order->parentOrderId] ?? null));
            $filtered = Hook::filter('vani.integration.order_payload', $payload, $order);

            // Plugin chỉ được THÊM field: khoá canonical giữ nguyên giá trị Core (R20, schema có version).
            return is_array($filtered) ? $payload + array_diff_key($filtered, $payload) : $payload;
        }, $orders);
    }

    /**
     * @return array<string, mixed>
     */
    /**
     * @param  list<OrderLineData>  $lines
     */
    private function canonical(OrderData $order, array $lines, ?PriceBreakdown $pricing, ?string $parentNumber): array
    {
        return [
            'schema' => self::ORDER_SCHEMA,
            'number' => $order->number,
            'public_id' => $order->publicId,
            'source' => $order->source,
            // Đơn thay thế của đổi hàng (source = exchange, 0.3.32): số đơn gốc; đơn thường là null.
            'parent_order_number' => $parentNumber,
            'customer_id' => $order->customerId,
            'status' => $order->status->value,
            'payment_status' => $order->paymentStatus,
            'fulfillment_status' => $order->fulfillmentStatus,
            'return_status' => $order->returnStatus,
            'payment_method' => $order->paymentMethod,
            'currency' => $order->currencyCode,
            'total_amount' => $order->totalAmount,
            'customer' => $order->recipient,
            'shipping_address' => $order->shippingAddress,
            'lines' => array_map(fn (OrderLineData $line): array => [
                'line_id' => $line->id,
                'variant_id' => $line->variantId,
                'sku' => $line->sku,
                'name' => $line->productName,
                'brand' => $line->brandName,
                // Tổ hợp tuỳ chọn của SKU lúc đặt (ADR-045): [{type, name, code, value}] theo thứ tự trục.
                'variant_options' => $line->variantOptions,
                'quantity' => $line->quantity,
                'total_amount' => $line->totalAmount,
            ], array_values(array_filter($lines, fn (OrderLineData $line): bool => $line->quantity > 0))), // dòng huỷ hết: chỉ có trong order.lines_cancelled
            // Tầng giá (0.3.37): niêm yết, giảm giá bán, khuyến mãi, mã giảm giá, giảm khác, phí giao, thuế, tổng.
            'pricing' => $pricing?->toArray(),
            'placed_at' => $order->placedAt,
            'updated_at' => $order->updatedAt,
        ];
    }
}
