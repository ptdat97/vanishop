<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Modules\Extension\Facades\Hook;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\Data\OrderLineData;
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
        $payload = $this->canonical($order);
        $filtered = Hook::filter('vani.integration.order_payload', $payload, $order);

        // Plugin chỉ được THÊM field: khoá canonical giữ nguyên giá trị Core (R20, schema có version).
        return is_array($filtered) ? $payload + array_diff_key($filtered, $payload) : $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function canonical(OrderData $order): array
    {
        return [
            'schema' => self::ORDER_SCHEMA,
            'number' => $order->number,
            'public_id' => $order->publicId,
            'source' => $order->source,
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
                'color' => $line->colorName,
                'size' => $line->sizeCode,
                'quantity' => $line->quantity,
                'total_amount' => $line->totalAmount,
            ], $this->orders->lines($order->id)),
            'placed_at' => $order->placedAt,
            'updated_at' => $order->updatedAt,
        ];
    }
}
