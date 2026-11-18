<?php

declare(strict_types=1);

namespace Modules\Returns\Application;

use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Returns\Contracts\Returns;
use Modules\Returns\Contracts\ReturnStatistics;
use Modules\Returns\Domain\ReturnStatus;

final class EloquentReturnStatistics implements ReturnStatistics
{
    public function __construct(private readonly Returns $returns) {}

    public function byVariant(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return $this->lines($from, $to)
            ->selectRaw('return_lines.variant_id, sum(return_lines.quantity) as quantity, sum(return_lines.refund_amount) as refund_amount')
            ->groupBy('return_lines.variant_id')->orderByDesc('quantity')->orderBy('return_lines.variant_id')->get()
            ->map(fn (object $row): array => ['variant_id' => (int) $row->variant_id, 'quantity' => (int) $row->quantity, 'refund_amount' => (int) $row->refund_amount])
            ->all();
    }

    public function byReason(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $locale = app()->getLocale();
        $storeFault = DB::table('return_reasons')->where('store_fault', true)->pluck('code')->all();
        // Phí ship cửa hàng chịu: theo yêu cầu (không nhân theo số dòng của yêu cầu).
        $storeShipping = DB::table('return_requests')
            ->where('status', ReturnStatus::Resolved->value)->where('return_shipping_payer', 'store')
            ->where('created_at', '>=', $this->utc($from))->where('created_at', '<', $this->utc($to))
            ->groupBy('reason_code')->selectRaw('reason_code, sum(return_shipping_fee) as fee')->pluck('fee', 'reason_code');

        return $this->lines($from, $to)
            ->selectRaw('return_requests.reason_code, count(distinct return_requests.id) as requests, sum(return_lines.quantity) as quantity')
            ->groupBy('return_requests.reason_code')->orderByDesc('requests')->orderBy('return_requests.reason_code')->get()
            ->map(fn (object $row): array => [
                'reason_code' => (string) $row->reason_code, 'label' => $this->returns->reasonLabel((string) $row->reason_code, $locale),
                'store_fault' => in_array((string) $row->reason_code, $storeFault, true),
                'requests' => (int) $row->requests, 'quantity' => (int) $row->quantity,
                'store_shipping_fee' => (int) ($storeShipping[(string) $row->reason_code] ?? 0),
            ])->all();
    }

    private function utc(DateTimeImmutable $at): string
    {
        return $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    private function lines(DateTimeImmutable $from, DateTimeImmutable $to): Builder
    {
        $utc = fn (DateTimeImmutable $at): string => $this->utc($at);

        return DB::table('return_lines')
            ->join('return_requests', 'return_requests.id', '=', 'return_lines.return_request_id')
            ->whereNotIn('return_requests.status', [ReturnStatus::Rejected->value, ReturnStatus::Cancelled->value])
            ->where('return_requests.created_at', '>=', $utc($from))
            ->where('return_requests.created_at', '<', $utc($to));
    }
}
