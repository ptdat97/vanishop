<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Ordering\Domain\OrderPolicy;
use Modules\Ordering\Domain\OrderStage;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Ordering\Persistence\Models\OrderAdjustment;
use Modules\Ordering\Persistence\Models\OrderLine;
use Modules\Shared\Support\Phones;
use Modules\Shared\Support\StoreClock;

final class OrderQueries
{
    /**
     * Danh sách đơn Admin. Lọc: `status`, `payment_status`, `stage` (OrderStage), `payment_method`, `source`, `from`/`to`
     * (ngày đặt Y-m-d theo giờ cửa hàng), `q` (số đơn hoặc SĐT), `ids`. Mỗi đơn có thêm thuộc tính `stage`.
     *
     * @param  array<string, mixed>  $filters  ids: giới hạn theo bộ lọc của plugin
     * @return LengthAwarePaginator<int, Order>
     */
    public function search(array $filters, int $perPage = 30): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->select('orders.*')->selectRaw(OrderStage::sql().' as stage')
            ->with('lines')
            ->orderByDesc('placed_at')->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Số đơn theo giai đoạn với cùng bộ lọc (trừ `stage`) — số trên các thẻ hàng đợi.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function stageCounts(array $filters): array
    {
        $counts = $this->filtered([...$filters, 'stage' => null])
            ->toBase()
            ->selectRaw(OrderStage::sql().' as stage, count(*) as total')
            ->groupBy('stage')
            ->pluck('total', 'stage');

        return array_map('intval', array_replace(array_fill_keys(OrderStage::STAGES, 0), $counts->all()));
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Order>
     */
    private function filtered(array $filters): Builder
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $phone = $q === '' ? null : Phones::parse($q);
        $value = fn (string $key): string => trim((string) ($filters[$key] ?? ''));
        $stage = $value('stage');
        // Ngày theo giờ cửa hàng → mốc UTC; `to` gồm cả ngày đó.
        $date = function (string $key, bool $end) use ($value): ?CarbonImmutable {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value($key)) !== 1) {
                return null;
            }
            $day = CarbonImmutable::createFromFormat('!Y-m-d', $value($key), StoreClock::zone());

            return $day === false ? null : ($end ? $day->addDay() : $day);
        };

        return Order::query()
            ->when($value('status') !== '', fn ($query) => $query->where('order_status', $value('status')))
            ->when($value('payment_status') !== '', fn ($query) => $query->where('payment_status', $value('payment_status')))
            ->when($value('payment_method') !== '', fn ($query) => $query->where('payment_method', $value('payment_method')))
            ->when($value('source') !== '', fn ($query) => $query->where('source', $value('source')))
            ->when(in_array($stage, OrderStage::STAGES, true), fn ($query) => $query->whereRaw('('.OrderStage::sql().') = ?', [$stage]))
            ->when($date('from', false) !== null, fn ($query) => $query->where('placed_at', '>=', $date('from', false)->utc()))
            ->when($date('to', true) !== null, fn ($query) => $query->where('placed_at', '<', $date('to', true)->utc()))
            ->when($q !== '', fn ($query) => $phone !== null
                ? $query->where('customer_phone', $phone->e164)
                : $query->where('number', strtoupper($q)))
            ->when(isset($filters['ids']), fn ($query) => $query->whereIn('id', $filters['ids']));
    }

    public function detail(Order $order, bool $masked = false): OrderDetail
    {
        $customer = (array) $order->customer_snapshot;
        $address = array_map('strval', (array) $order->shipping_address);
        if ($masked) {
            $customer = ['full_name' => self::maskName((string) ($customer['full_name'] ?? '')), 'phone' => Phones::parse((string) ($customer['phone'] ?? ''))?->masked() ?? '', 'email' => null];
            $address = ['province_name' => $address['province_name'] ?? '', 'ward_name' => $address['ward_name'] ?? '', 'street_line' => '***'];
        }

        return hook_filter('vani.service.ordering.order_detail', new OrderDetail(
            id: $order->id,
            publicId: $order->public_id,
            number: $order->number,
            orderStatus: $order->order_status->value,
            paymentStatus: (string) $order->payment_status,
            fulfillmentStatus: (string) $order->fulfillment_status,
            returnStatus: (string) $order->return_status,
            paymentMethod: (string) $order->payment_method,
            customerStatus: CustomerStatusLabel::of($order->order_status->value, (string) $order->payment_status, (string) $order->fulfillment_status, (string) $order->return_status),
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
            receipt: OrderReceipt::of($order),
        ), $order, $masked);
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
