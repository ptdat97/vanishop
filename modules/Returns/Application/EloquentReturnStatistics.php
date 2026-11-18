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

        return $this->lines($from, $to)
            ->selectRaw('return_requests.reason_code, count(distinct return_requests.id) as requests, sum(return_lines.quantity) as quantity')
            ->groupBy('return_requests.reason_code')->orderByDesc('requests')->orderBy('return_requests.reason_code')->get()
            ->map(fn (object $row): array => [
                'reason_code' => (string) $row->reason_code, 'label' => $this->returns->reasonLabel((string) $row->reason_code, $locale),
                'requests' => (int) $row->requests, 'quantity' => (int) $row->quantity,
            ])->all();
    }

    private function lines(DateTimeImmutable $from, DateTimeImmutable $to): Builder
    {
        $utc = fn (DateTimeImmutable $at): string => $at->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

        return DB::table('return_lines')
            ->join('return_requests', 'return_requests.id', '=', 'return_lines.return_request_id')
            ->whereNotIn('return_requests.status', [ReturnStatus::Rejected->value, ReturnStatus::Cancelled->value])
            ->where('return_requests.created_at', '>=', $utc($from))
            ->where('return_requests.created_at', '<', $utc($to));
    }
}
