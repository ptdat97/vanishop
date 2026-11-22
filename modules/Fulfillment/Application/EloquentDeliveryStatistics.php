<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Fulfillment\Contracts\DeliveryStatistics;
use Modules\Fulfillment\Domain\ShipmentStatus;

final class EloquentDeliveryStatistics implements DeliveryStatistics
{
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
