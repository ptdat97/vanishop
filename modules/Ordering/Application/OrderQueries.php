<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Domain\OrderPolicy;
use Modules\Ordering\Domain\OrderStage;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Ordering\Persistence\Models\OrderAdjustment;
use Modules\Ordering\Persistence\Models\OrderLine;
use Modules\Shared\Http\ListSort;
use Modules\Shared\Support\Phones;
use Modules\Shared\Support\StoreClock;

final class OrderQueries
{
    /**
     * Danh sách đơn Admin. Lọc: `status`, `payment_status`, `stage` (OrderStage), `aged` (giờ), `payment_method`, `source`, `from`/`to`
     * (ngày đặt Y-m-d theo giờ cửa hàng), `q` (số đơn hoặc SĐT), `ids`. Mỗi đơn có thêm thuộc tính `stage`.
     *
     * @param  array<string, mixed>  $filters  ids: giới hạn theo bộ lọc của plugin
     * @param  ListSort|null  $sort  sắp theo cột người dùng chọn; null → hàng đợi cũ nhất trước, còn lại mới nhất trước
     * @return LengthAwarePaginator<int, Order>
     */
    public function search(array $filters, int $perPage = 30, ?ListSort $sort = null): LengthAwarePaginator
    {
        return $this->filtered($filters)
            ->select('orders.*')->selectRaw(OrderStage::sql().' as stage')
            ->with('lines')
            ->when($sort,
                fn ($query) => $sort->apply($query, 'orders.id'),
                fn ($query) => (int) ($filters['aged'] ?? 0) > 0 || (string) ($filters['actionable'] ?? '') === '1'
                    ? $query->orderBy('placed_at')->orderBy('id')
                    : $query->orderByDesc('placed_at')->orderByDesc('id'))
            ->paginate($perPage)
            ->withQueryString();
    }

    /** Tối đa số đơn một lần xuất file (A8). */
    public const EXPORT_MAX = 10_000;

    /**
     * Xuất đơn ra file theo đúng bộ lọc của danh sách (A8): `orders` mỗi đơn một dòng, `lines` mỗi dòng SKU một dòng (cho kế
     * toán, đối chiếu). Mới nhất trước, tối đa EXPORT_MAX đơn.
     *
     * @param  array<string, mixed>  $filters
     * @return list<list<string|int|null>> dòng đầu là tiêu đề
     */
    public function export(array $filters, string $mode): array
    {
        $orderStatus = (array) __('ordering::labels.order_status');
        $paymentStatus = (array) __('ordering::labels.payment_status');
        $lines = $mode === 'lines';
        $rows = [$lines
            ? [__('ordering::texts.order_queries.orders'), __('ordering::texts.order_queries.order_date'), __('ordering::texts.order_queries.status'), 'SKU', __('ordering::texts.order_queries.products'), __('ordering::texts.order_queries.custom'), __('ordering::texts.order_queries.quantity'), __('ordering::texts.order_queries.cancelled'), __('ordering::texts.order_queries.unit_price'), __('ordering::texts.order_queries.discount'), __('ordering::texts.order_queries.amount')]
            : [__('ordering::texts.order_queries.orders'), __('ordering::texts.order_queries.order_date'), __('ordering::texts.order_queries.status'), __('ordering::texts.order_queries.payment'), __('ordering::texts.order_queries.method'), __('ordering::texts.order_queries.source'), __('ordering::texts.order_queries.customers'), __('ordering::texts.order_queries.phone_number'), 'Email', __('ordering::texts.order_queries.province_city'), __('ordering::texts.order_queries.ward'), __('ordering::texts.order_queries.address'), __('ordering::texts.order_queries.subtotal'), __('ordering::texts.order_queries.discount'), __('ordering::texts.order_queries.shipping_fee'), __('ordering::texts.order_queries.total'), __('ordering::texts.order_queries.note')]];
        $this->filtered($filters)->select('orders.*')->with($lines ? 'lines' : [])->orderByDesc('placed_at')->orderByDesc('id')
            ->limit(self::EXPORT_MAX)->get()
            ->each(function (Order $order) use (&$rows, $lines, $orderStatus, $paymentStatus): void {
                $placed = StoreClock::format($order->placed_at);
                $status = $orderStatus[$order->order_status->value] ?? $order->order_status->value;
                if ($lines) {
                    foreach ($order->lines as $line) {
                        $options = implode(' / ', array_map(fn (array $option): string => $option['value'], $line->variant_options ?? []));
                        $rows[] = [$order->number, $placed, $status, $line->sku, $line->product_name, $options, $line->quantity, $line->cancelled_quantity, $line->unit_amount, $line->discount_amount, $line->total_amount];
                    }

                    return;
                }
                $address = $order->shipping_address;
                $rows[] = [
                    $order->number, $placed, $status, $paymentStatus[$order->payment_status] ?? $order->payment_status, $order->payment_method, $order->source,
                    $order->customer_snapshot['full_name'] ?? null, $order->customer_phone, $order->customer_snapshot['email'] ?? null,
                    $address['province_name'] ?? null, $address['ward_name'] ?? null, $address['street_line'] ?? null,
                    $order->subtotal_amount, $order->discount_amount, $order->shipping_amount, $order->total_amount, $order->note,
                ];
            });

        return $rows;
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
     * Đơn chưa xác nhận hoặc chưa giao hãng mà đã đặt quá `$hours` giờ (ô "Việc cần làm", bộ lọc `aged`).
     */
    /**
     * Lịch sử nhận hàng theo SĐT cho nhiều SĐT một lúc (danh sách đơn "Cần xác nhận" — chốt ①, ADR-046): một truy vấn
     * cho cả trang. Số đơn tính cả đơn đang xem; khoá là SĐT dạng E.164 như `orders.customer_phone`.
     *
     * @param  list<string>  $phones
     * @return array<string, array{orders: int, delivered: int, returned_to_sender: int, cancelled: int}>
     */
    public function deliveryHistoryByPhone(array $phones): array
    {
        $phones = array_values(array_unique(array_filter($phones, fn (string $phone): bool => $phone !== '')));
        if ($phones === []) {
            return [];
        }

        return Order::query()->whereIn('customer_phone', $phones)->groupBy('customer_phone')
            ->selectRaw("customer_phone, count(*) as orders, sum(case when fulfillment_status = 'delivered' then 1 else 0 end) as delivered, sum(case when fulfillment_status = 'returned_to_sender' then 1 else 0 end) as returned, sum(case when order_status = 'cancelled' then 1 else 0 end) as cancelled")
            ->toBase()->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->customer_phone => [
                'orders' => (int) $row->orders, 'delivered' => (int) $row->delivered, 'returned_to_sender' => (int) $row->returned, 'cancelled' => (int) $row->cancelled,
            ]])->all();
    }

    /**
     * Đơn đã hoàn tất mà tiền thu khi giao chưa ghi nhận (thường do hoàn tất ngoài trình tự) — ô "Việc cần làm".
     */
    public function completedAwaitingCodCount(): int
    {
        return Order::query()->where('order_status', OrderStatus::Completed)->where('payment_status', 'cod_pending')->count();
    }

    public function agedCount(int $hours): int
    {
        return $this->filtered(['aged' => $hours])->count();
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

            return $day === null ? null : ($end ? $day->addDay() : $day);
        };

        return Order::query()
            ->when($value('status') !== '', fn ($query) => $query->where('order_status', $value('status')))
            ->when($value('payment_status') !== '', fn ($query) => $query->where('payment_status', $value('payment_status')))
            ->when($value('payment_method') !== '', fn ($query) => $query->where('payment_method', $value('payment_method')))
            ->when($value('source') !== '', fn ($query) => $query->where('source', $value('source')))
            ->when(in_array($stage, OrderStage::STAGES, true), fn ($query) => $query->whereRaw('('.OrderStage::sql().') = ?', [$stage]))
            ->when($value('actionable') === '1', fn ($query) => $query->whereIn(DB::raw('('.OrderStage::sql().')'), OrderStage::ACTIONABLE))
            // `aged` (giờ): đơn đang chờ xác nhận / chờ giao hãng đặt quá N giờ — chưa được xử lý kịp.
            ->when((int) $value('aged') > 0, fn ($query) => $query->whereRaw('('.OrderStage::sql().") in ('to_confirm', 'to_ship')")->where('placed_at', '<', now()->subHours((int) $value('aged'))))
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

        return vani_filter('vani.service.ordering.order_detail', new OrderDetail(
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
            placedAt: (string) StoreClock::iso($order->placed_at),
            lines: $order->lines->map(fn (OrderLine $line): array => [
                'id' => $line->id, 'variant_id' => $line->variant_id, 'sku' => $line->sku, 'name' => $line->product_name, 'brand_name' => $line->brand_name,
                'variant_options' => (array) ($line->variant_options ?? []), 'variant_label' => VariantOptionsText::label((array) ($line->variant_options ?? [])),
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
                'at' => StoreClock::iso((string) $event->created_at),
                // Mốc thời gian (giây, UTC) để Admin gộp với sự kiện vận đơn/thanh toán (0.3.96).
                'ts' => strtotime((string) $event->created_at.' UTC') ?: 0,
            ])->all();
    }

    private static function maskName(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $last = array_pop($parts) ?? '';

        return trim(implode(' ', array_map(fn (string $part): string => mb_substr($part, 0, 1).'.', $parts)).' '.$last);
    }
}
