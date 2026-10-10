<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Ordering\Domain\CustomerStatus;
use Modules\Ordering\Domain\OrderPolicy;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Ordering\Persistence\Models\OrderAdjustment;
use Modules\Ordering\Persistence\Models\OrderLine;
use Modules\Shared\Support\Phones;
use Modules\Shared\Support\StoreClock;

final class OrderQueries
{
    /**
     * @param  array{status?: ?string, payment_status?: ?string, q?: ?string, ids?: list<int>|null}  $filters  ids: giới hạn theo bộ lọc của plugin
     * @return LengthAwarePaginator<int, Order>
     */
    public function search(array $filters, int $perPage = 30): LengthAwarePaginator
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $phone = $q === '' ? null : Phones::parse($q);

        return Order::query()
            ->when(($filters['status'] ?? null) !== null && $filters['status'] !== '', fn ($query) => $query->where('order_status', $filters['status']))
            ->when(($filters['payment_status'] ?? null) !== null && $filters['payment_status'] !== '', fn ($query) => $query->where('payment_status', $filters['payment_status']))
            ->when($q !== '', fn ($query) => $phone !== null
                ? $query->where('customer_phone', $phone->e164)
                : $query->where('number', strtoupper($q)))
            ->when(isset($filters['ids']), fn ($query) => $query->whereIn('id', $filters['ids']))
            ->orderByDesc('placed_at')->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function detail(Order $order, bool $masked = false): OrderDetail
    {
        $customer = (array) $order->customer_snapshot;
        $address = array_map('strval', (array) $order->shipping_address);
        if ($masked) {
            $customer = ['full_name' => self::maskName((string) ($customer['full_name'] ?? '')), 'phone' => Phones::parse((string) ($customer['phone'] ?? ''))?->masked() ?? '', 'email' => null];
            $address = ['province_name' => $address['province_name'] ?? '', 'ward_name' => $address['ward_name'] ?? '', 'street_line' => '***'];
        }

        return new OrderDetail(
            id: $order->id,
            publicId: $order->public_id,
            number: $order->number,
            orderStatus: $order->order_status->value,
            paymentStatus: (string) $order->payment_status,
            fulfillmentStatus: (string) $order->fulfillment_status,
            returnStatus: (string) $order->return_status,
            paymentMethod: (string) $order->payment_method,
            customerStatus: CustomerStatus::of($order->order_status->value, (string) $order->payment_status, (string) $order->fulfillment_status, (string) $order->return_status),
            currencyCode: $order->currency_code,
            amounts: ['subtotal' => $order->subtotal_amount, 'discount' => $order->discount_amount, 'shipping' => $order->shipping_amount, 'tax' => $order->tax_amount, 'total' => $order->total_amount],
            customer: ['full_name' => (string) ($customer['full_name'] ?? ''), 'phone' => (string) ($customer['phone'] ?? ''), 'email' => $customer['email'] ?? null],
            shippingAddress: $address,
            shippingMethod: (array) $order->shipping_method,
            note: $masked ? null : $order->note,
            placedAt: StoreClock::format($order->placed_at),
            lines: $order->lines->map(fn (OrderLine $line): array => [
                'id' => $line->id, 'variant_id' => $line->variant_id, 'sku' => $line->sku, 'name' => $line->product_name, 'brand_name' => $line->brand_name, 'color_name' => $line->color_name, 'size_code' => $line->size_code,
                'image_url' => $line->image_url, 'quantity' => $line->quantity, 'cancelled_quantity' => $line->cancelled_quantity, 'unit_amount' => $line->unit_amount, 'compare_at_amount' => $line->compare_at_amount,
                'discount_amount' => $line->discount_amount, 'total_amount' => $line->total_amount, 'tax_rate_bp' => $line->tax_rate_bp, 'tax_amount' => $line->tax_amount,
                'options' => (array) ($line->meta['options'] ?? []),
            ])->all(),
            adjustments: $order->adjustments->map(fn (OrderAdjustment $adjustment): array => [
                'type' => $adjustment->type, 'code' => $adjustment->code, 'label' => $adjustment->label, 'amount' => $adjustment->amount,
            ])->all(),
            events: $this->events($order->id, internal: ! $masked),
            cancellableByCustomer: OrderPolicy::customerCanCancel($order->order_status, (string) $order->fulfillment_status),
            lockVersion: $order->lock_version,
            source: (string) $order->source,
            pricing: (new PriceBreakdownCalculator)->for($order)->toArray(),
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function events(int $orderId, bool $internal): array
    {
        return DB::table('order_events')->where('order_id', $orderId)
            ->when(! $internal, fn ($query) => $query->whereIn('type', ['placed', 'status_changed']))
            ->orderBy('id')->get()
            ->map(fn (object $event): array => [
                'type' => $event->type,
                'from' => $event->from_status,
                'to' => $event->to_status,
                'reason' => $internal ? $event->reason : null,
                'source' => $internal ? $event->source : null,
                'actor' => $internal && $event->actor_type !== null ? $event->actor_type.($event->actor_id !== null ? '#'.$event->actor_id : '') : null,
                'data' => $internal && $event->data !== null ? json_decode((string) $event->data, true) : null,
                'at' => StoreClock::format((string) $event->created_at),
            ])->all();
    }

    private static function maskName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $last = array_pop($parts) ?? '';

        return trim(implode(' ', array_map(fn (string $part): string => mb_substr($part, 0, 1).'.', $parts)).' '.$last);
    }
}
