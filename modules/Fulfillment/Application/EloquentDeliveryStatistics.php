<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Fulfillment\Contracts\DeliveryStatistics;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Payment\Contracts\Reconciliations;

final class EloquentDeliveryStatistics implements DeliveryStatistics
{
    public function __construct(private readonly Reconciliations $reconciliations) {}

    public function carrierFees(array $orderIds): array
    {
        $result = [];
        foreach (array_chunk(array_values(array_unique(array_map('intval', $orderIds))), 1000) as $chunk) {
            $rows = DB::table('shipments')->whereIn('order_id', $chunk)->whereNotNull('shipped_at')
                ->groupBy('order_id')
                ->selectRaw('order_id, sum(coalesce(actual_carrier_fee, 0)) as fee, sum(case when actual_carrier_fee is null then 1 else 0 end) as missing')
                ->get();
            foreach ($rows as $row) {
                $result[(int) $row->order_id] = ['fee' => (int) $row->fee, 'missing' => (int) $row->missing];
            }
        }

        return $result;
    }

    public function codAging(DateTimeImmutable $from, DateTimeImmutable $to, DateTimeImmutable $now): array
    {
        $utc = new DateTimeZone('UTC');
        $rows = [];
        $delivered = DB::table('shipments')->where('status', ShipmentStatus::Delivered->value)->where('cod_amount', '>', 0)
            ->where('delivered_at', '>=', $from->setTimezone($utc))->where('delivered_at', '<', $to->setTimezone($utc))
            ->where('delivered_at', '<=', $now->setTimezone($utc))
            ->select(['id', 'order_id', 'carrier_code', 'tracking_number', 'cod_amount', 'delivered_at']);
        foreach ($delivered->lazyById(500)->chunk(500) as $shipments) {
            $references = $shipments->pluck('tracking_number')->filter()->map(fn (string $code): string => mb_strtoupper($code))->values()->all();
            $matched = $this->reconciliations->matchedOrders(CodStatement::SOURCE, $references);
            foreach ($shipments as $shipment) {
                if (($matched[mb_strtoupper((string) $shipment->tracking_number)] ?? null) === (int) $shipment->order_id) {
                    continue;
                }
                $days = intdiv($now->getTimestamp() - (new DateTimeImmutable((string) $shipment->delivered_at, $utc))->getTimestamp(), 86400);
                $bucket = match (true) {
                    $days < 7 => '0-6',
                    $days < 15 => '7-14',
                    $days < 30 => '15-29',
                    default => '30+',
                };
                $carrier = (string) $shipment->carrier_code;
                $rows[$carrier][$bucket] ??= ['carrier' => $carrier, 'bucket' => $bucket, 'shipments' => 0, 'amount' => 0];
                $rows[$carrier][$bucket]['shipments']++;
                $rows[$carrier][$bucket]['amount'] += (int) $shipment->cod_amount;
            }
        }
        ksort($rows);

        return array_merge(...array_map(static function (array $buckets): array {
            uksort($buckets, fn (string $a, string $b): int => (int) $a <=> (int) $b);

            return array_values($buckets);
        }, array_values($rows)));
    }

    public function cod(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $utc = fn (DateTimeImmutable $at): string => $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        [$start, $end] = [$utc($from), $utc($to)];
        $delivered = ShipmentStatus::Delivered->value;
        $failed = [ShipmentStatus::Returning->value, ShipmentStatus::Returned->value];
        $row = DB::table('shipments')->where('cod_amount', '>', 0)
            ->where(fn (Builder $query) => $query
                ->where(fn (Builder $q) => $q->where('status', $delivered)->where('delivered_at', '>=', $start)->where('delivered_at', '<', $end))
                ->orWhere(fn (Builder $q) => $q->whereIn('status', $failed)->where('delivery_failed_at', '>=', $start)->where('delivery_failed_at', '<', $end)))
            ->selectRaw('count(*) as total, sum(case when status = ? then 1 else 0 end) as delivered, sum(case when status in (?, ?) then 1 else 0 end) as failed, sum(case when status in (?, ?) then cod_amount else 0 end) as failed_amount', [$delivered, ...$failed, ...$failed])
            ->first();

        return ['cod_shipments' => (int) ($row->total ?? 0), 'delivered' => (int) ($row->delivered ?? 0), 'failed' => (int) ($row->failed ?? 0), 'failed_cod_amount' => (int) ($row->failed_amount ?? 0)];
    }
}
