<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Admin;

use Illuminate\Support\Facades\Gate;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\ReportProvider;

/**
 * Báo cáo trong Admin (W6b): danh sách báo cáo nhân viên được xem, chạy báo cáo theo khoảng thời gian.
 */
final class Reports
{
    public function __construct(private readonly Extensions $extensions) {}

    /**
     * @return array<string, ReportProvider>
     */
    public function visible(): array
    {
        return array_filter(
            $this->extensions->implementations(ReportProvider::TAG, ReportProvider::class, fn (ReportProvider $report): string => $report->key()),
            fn (ReportProvider $report): bool => $report->permission() === null || Gate::allows($report->permission()),
        );
    }

    public function find(string $key): ?ReportProvider
    {
        return $this->visible()[$key] ?? null;
    }

    /**
     * Lỗi của plugin khi chạy báo cáo được ghi log; null = không chạy được.
     */
    public function run(ReportProvider $report, ReportPeriod $period): ?ReportResult
    {
        return $this->extensions->call($report, fn (): ReportResult => $report->run($period), null, 'report');
    }
}
