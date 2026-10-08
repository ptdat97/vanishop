<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\Data\SalesBucket;
use Modules\Ordering\Contracts\Data\SalesDimension;
use Modules\Ordering\Contracts\Data\SalesTotals;
use Modules\Ordering\Contracts\OrderStatistics;

final class EloquentOrderStatistics implements OrderStatistics
{
    public function totals(DateTimeImmutable $from, DateTimeImmutable $to): SalesTotals
    {
        $this->guardRange($from, $to);

        $row = $this->orders($from, $to)
            ->selectRaw('count(*) as orders_count, coalesce(sum(total_amount), 0) as revenue, coalesce(sum(discount_amount), 0) as discount, coalesce(sum(shipping_amount), 0) as shipping, count(distinct customer_id) as customers_count')
            ->first();

        $cancelled = DB::table('orders')
            ->where('order_status', OrderStatus::Cancelled->value)
            ->where('placed_at', '>=', $this->utc($from))
            ->where('placed_at', '<', $this->utc($to))
            ->count();

        return new SalesTotals(
            (int) ($row->orders_count ?? 0), (int) ($row->revenue ?? 0), (int) ($row->discount ?? 0),
            (int) ($row->shipping ?? 0), $cancelled, (int) ($row->customers_count ?? 0),
        );
    }

    public function summarize(array $orderIds): SalesTotals
    {
        $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
        if ($orderIds === []) {
            return new SalesTotals(0, 0, 0, 0, 0, 0);
        }

        $rows = collect(array_chunk($orderIds, 1000))->map(fn (array $chunk) => DB::table('orders')->whereIn('id', $chunk)
            ->selectRaw('sum(case when order_status <> ? then 1 else 0 end) as orders_count, sum(case when order_status <> ? then total_amount else 0 end) as revenue, sum(case when order_status <> ? then discount_amount else 0 end) as discount, sum(case when order_status <> ? then shipping_amount else 0 end) as shipping, sum(case when order_status = ? then 1 else 0 end) as cancelled', array_fill(0, 5, OrderStatus::Cancelled->value))
            ->first());
        $customers = DB::table('orders')->whereIn('id', $orderIds)->where('order_status', '<>', OrderStatus::Cancelled->value)->whereNotNull('customer_id')->distinct()->count('customer_id');

        return new SalesTotals(
            (int) $rows->sum('orders_count'), (int) $rows->sum('revenue'), (int) $rows->sum('discount'),
            (int) $rows->sum('shipping'), (int) $rows->sum('cancelled'), $customers,
        );
    }

    public function daily(DateTimeImmutable $from, DateTimeImmutable $to, string $timezone): array
    {
        $this->guardRange($from, $to);
        $zone = new DateTimeZone($timezone);

        /** @var array<string, array{0: int, 1: int}> $days */
        $days = [];
        for ($day = $from->setTimezone($zone)->setTime(0, 0); $day < $to; $day = $day->modify('+1 day')) {
            $days[$day->format('Y-m-d')] = [0, 0];
        }

        // Nhóm theo ngày ở PHP: hàm ngày/múi giờ khác nhau giữa MySQL và SQLite; chỉ đọc 2 cột, theo con trỏ.
        foreach ($this->orders($from, $to)->select(['placed_at', 'total_amount'])->orderBy('placed_at')->cursor() as $order) {
            $key = (new DateTimeImmutable((string) $order->placed_at, new DateTimeZone('UTC')))->setTimezone($zone)->format('Y-m-d');
            $days[$key] ??= [0, 0];
            $days[$key][0]++;
            $days[$key][1] += (int) $order->total_amount;
        }

        $buckets = [];
        foreach ($days as $key => [$count, $revenue]) {
            $buckets[] = new SalesBucket($key, (new DateTimeImmutable($key))->format('d/m'), $count, 0, $revenue);
        }

        return $buckets;
    }

    public function breakdown(SalesDimension $dimension, DateTimeImmutable $from, DateTimeImmutable $to, int $limit = 20): array
    {
        $this->guardRange($from, $to);
        $limit = max(1, min($limit, 100));

        $query = match ($dimension) {
            SalesDimension::PaymentMethod, SalesDimension::Source => $this->orders($from, $to)
                ->selectRaw("{$dimension->value} as bucket_key, max({$dimension->value}) as bucket_label, count(*) as orders_count, 0 as quantity, coalesce(sum(total_amount), 0) as revenue")
                ->groupBy($dimension->value),
            SalesDimension::Product => $this->lines($from, $to)
                ->selectRaw('order_lines.product_name as bucket_key, max(order_lines.product_name) as bucket_label, count(distinct order_lines.order_id) as orders_count, coalesce(sum(order_lines.quantity), 0) as quantity, coalesce(sum(order_lines.total_amount), 0) as revenue')
                ->groupBy('order_lines.product_name'),
            SalesDimension::Brand => $this->lines($from, $to)
                ->selectRaw("coalesce(order_lines.brand_name, '') as bucket_key, max(order_lines.brand_name) as bucket_label, count(distinct order_lines.order_id) as orders_count, coalesce(sum(order_lines.quantity), 0) as quantity, coalesce(sum(order_lines.total_amount), 0) as revenue")
                ->groupByRaw("coalesce(order_lines.brand_name, '')"),
        };

        return $query->orderByDesc('revenue')->orderBy('bucket_key')->limit($limit)->get()
            ->map(fn (object $row): SalesBucket => new SalesBucket(
                (string) $row->bucket_key, (string) ($row->bucket_label ?? ''), (int) $row->orders_count, (int) $row->quantity, (int) $row->revenue,
            ))
            ->all();
    }

    public function countByStatus(): array
    {
        $counts = array_fill_keys(array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::cases()), 0);

        foreach (DB::table('orders')->selectRaw('order_status, count(*) as total')->groupBy('order_status')->get() as $row) {
            $counts[(string) $row->order_status] = (int) $row->total;
        }

        return $counts;
    }

    private function orders(DateTimeImmutable $from, DateTimeImmutable $to): Builder
    {
        return DB::table('orders')
            ->where('order_status', '!=', OrderStatus::Cancelled->value)
            ->where('placed_at', '>=', $this->utc($from))
            ->where('placed_at', '<', $this->utc($to));
    }

    private function lines(DateTimeImmutable $from, DateTimeImmutable $to): Builder
    {
        return DB::table('order_lines')
            ->join('orders', 'orders.id', '=', 'order_lines.order_id')
            ->where('orders.order_status', '!=', OrderStatus::Cancelled->value)
            ->where('orders.placed_at', '>=', $this->utc($from))
            ->where('orders.placed_at', '<', $this->utc($to));
    }

    private function utc(DateTimeImmutable $at): string
    {
        return $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function guardRange(DateTimeImmutable $from, DateTimeImmutable $to): void
    {
        if ($to <= $from) {
            throw new InvalidArgumentException('Khoảng thời gian không hợp lệ: "to" phải sau "from".');
        }

        if ($from->diff($to)->days > self::MAX_DAYS) {
            throw new InvalidArgumentException('Khoảng thời gian tối đa '.self::MAX_DAYS.' ngày.');
        }
    }
}
