<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use DateTimeImmutable;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\Brand\Contracts\BrandDirectory;
use Modules\Ordering\Contracts\Data\OrderDraft;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\Data\PlacedOrder;
use Modules\Ordering\Contracts\OrderWriter;
use Modules\Ordering\Domain\OrderNumber;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Application\NumberSequences;
use Modules\Shared\Context\CurrentContext;

final class OrderFactory implements OrderWriter
{
    public function __construct(
        private readonly BrandDirectory $brands,
        private readonly NumberSequences $sequences,
        private readonly CurrentContext $context,
    ) {}

    public function create(OrderDraft $draft): PlacedOrder
    {
        $this->assertBalanced($draft);
        $brand = $this->brands->find($draft->brandId) ?? throw new InvalidArgumentException("Brand #{$draft->brandId} không tồn tại.");
        $now = new DateTimeImmutable('now', new \DateTimeZone('Asia/Ho_Chi_Minh'));
        $period = OrderNumber::period($now);
        $number = OrderNumber::format($brand->code, $period, $this->sequences->next("order:{$brand->id}", $period));

        $accessToken = Str::random(40);
        $order = Order::query()->create([
            'customer_phone' => $draft->customer['phone'], 'access_token_hash' => hash('sha256', $accessToken),
            'public_id' => $draft->publicId, 'number' => $number, 'legal_entity_id' => $brand->legalEntityId, 'brand_id' => $brand->id,
            'channel_id' => $draft->channelId, 'customer_id' => $draft->customerId, 'currency_code' => $draft->currencyCode,
            'order_status' => OrderStatus::Pending, 'payment_status' => $draft->paymentStatus, 'fulfillment_status' => 'unfulfilled', 'return_status' => 'none',
            'payment_method' => $draft->paymentMethod,
            'subtotal_amount' => $draft->subtotalAmount, 'discount_amount' => $draft->discountAmount, 'shipping_amount' => $draft->shippingAmount,
            'tax_amount' => $draft->taxAmount, 'total_amount' => $draft->totalAmount,
            'meta' => $draft->meta === [] ? null : $draft->meta, 'customer_snapshot' => $draft->customer, 'shipping_address' => $draft->shippingAddress, 'shipping_method' => $draft->shippingMethod,
            'note' => $draft->note, 'reservation_key' => $draft->reservationKey, 'source_cart_id' => $draft->sourceCartId, 'placed_at' => now(),
        ]);

        foreach ($draft->lines as $line) {
            $order->lines()->create([
                'variant_id' => $line->variantId, 'sku' => $line->sku, 'product_name' => $line->productName, 'color_name' => $line->colorName,
                'size_code' => $line->sizeCode, 'image_url' => $line->imageUrl, 'quantity' => $line->quantity, 'unit_amount' => $line->unitAmount,
                'compare_at_amount' => $line->compareAtAmount, 'subtotal_amount' => $line->subtotalAmount, 'discount_amount' => $line->discountAmount,
                'total_amount' => $line->totalAmount, 'tax_rate_bp' => $line->taxRateBp, 'tax_amount' => $line->taxAmount,
            ]);
        }
        foreach ($draft->adjustments as $adjustment) {
            $order->adjustments()->create([
                'type' => $adjustment->type, 'source' => $adjustment->source, 'code' => $adjustment->code, 'label' => $adjustment->label,
                'amount' => $adjustment->amount, 'meta' => $adjustment->meta === [] ? null : $adjustment->meta,
            ]);
        }

        $actor = $this->context->has() ? $this->context->actor() : null;
        DB::table('order_events')->insert([
            'order_id' => $order->id, 'type' => 'placed', 'from_status' => null, 'to_status' => OrderStatus::Pending->value,
            'actor_type' => $actor?->type->value, 'actor_id' => $actor?->id, 'source' => 'customer', 'correlation_id' => Context::get('correlation_id'),
            'data' => json_encode(['payment_method' => $draft->paymentMethod, 'total' => $draft->totalAmount]), 'created_at' => now(),
        ]);

        event(new OrderPlaced($order->id, $order->public_id, $number, $brand->id, $draft->channelId, $draft->customerId, $draft->totalAmount, $draft->currencyCode));

        return new PlacedOrder($order->id, $order->public_id, $number, $brand->id, $brand->legalEntityId, $draft->channelId, OrderStatus::Pending->value, $draft->paymentStatus, $draft->totalAmount, $draft->currencyCode, $accessToken);
    }

    /**
     * Invariant snapshot: tổng dòng khớp tổng đơn (không để Checkout lưu số lệch).
     */
    private function assertBalanced(OrderDraft $draft): void
    {
        $lines = array_sum(array_map(fn ($line): int => $line->totalAmount, $draft->lines));
        $subtotal = array_sum(array_map(fn ($line): int => $line->subtotalAmount, $draft->lines));
        $discount = array_sum(array_map(fn ($line): int => $line->discountAmount, $draft->lines));

        if ($draft->lines === [] || $subtotal !== $draft->subtotalAmount || $discount !== $draft->discountAmount
            || $lines + $draft->shippingAmount !== $draft->totalAmount || $draft->totalAmount < 0) {
            throw new InvalidArgumentException('Số liệu đơn không cân: tổng dòng/giảm giá/phí không khớp tổng đơn.');
        }
    }

    public function reassignCustomer(int $fromCustomerId, int $toCustomerId): int
    {
        return DB::transaction(function () use ($fromCustomerId, $toCustomerId): int {
            $orders = Order::query()->withoutGlobalScopes()->where('customer_id', $fromCustomerId)->lockForUpdate()->get(['id']);
            foreach ($orders as $order) {
                Order::query()->withoutGlobalScopes()->whereKey($order->id)->update(['customer_id' => $toCustomerId, 'updated_at' => now()]);
                $actor = $this->context->has() ? $this->context->actor() : null;
                DB::table('order_events')->insert([
                    'order_id' => $order->id, 'type' => 'customer_reassigned', 'from_status' => null, 'to_status' => null, 'reason' => 'customer_merge',
                    'actor_type' => $actor?->type->value, 'actor_id' => $actor?->id, 'source' => 'staff', 'correlation_id' => Context::get('correlation_id'),
                    'data' => json_encode(['from' => $fromCustomerId, 'to' => $toCustomerId]), 'created_at' => now(),
                ]);
            }

            return $orders->count();
        });
    }
}
