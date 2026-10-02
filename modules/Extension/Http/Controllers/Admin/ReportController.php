<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Modules\Extension\Application\Admin\Reports;
use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\ReportProvider;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin → Báo cáo: Core dựng trang và CSV, plugin (vd. `vani.reports`) cung cấp ReportProvider.
 */
final class ReportController
{
    public function __construct(private readonly Reports $reports) {}

    public function index(): Response
    {
        Gate::authorize('admin.access');

        return Inertia::render('Extension::Reports/Index', [
            'reports' => array_values(array_map(fn (ReportProvider $report): array => [
                'key' => $report->key(), 'label' => $report->label(), 'description' => $report->description(),
            ], $this->reports->visible())),
        ]);
    }

    public function show(Request $request, string $key): Response
    {
        $report = $this->report($key);
        $period = $this->period($request);
        $result = $this->reports->run($report, $period);

        return Inertia::render('Extension::Reports/Show', [
            'report' => ['key' => $report->key(), 'label' => $report->label(), 'description' => $report->description()],
            'period' => $period->toArray(),
            'presets' => ReportPeriod::PRESETS,
            'result' => $result?->toArray(),
        ]);
    }

    public function export(Request $request, string $key): StreamedResponse
    {
        $report = $this->report($key);
        $period = $this->period($request);
        $result = $this->reports->run($report, $period);
        abort_if($result === null, 503, 'Báo cáo tạm thời không chạy được.');

        $filename = sprintf('%s_%s_%s.csv', $report->key(), $period->from->format('Ymd'), str_replace('-', '', $period->lastDay()));

        return response()->streamDownload(function () use ($result): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF"); // BOM để Excel đọc đúng UTF-8
            fputcsv($out, array_map(fn (Column $column): string => $column->label, $result->table->columns), escape: '');
            foreach ($result->table->rows as $row) {
                fputcsv($out, array_map(fn (Column $column): string => self::cell($row[$column->key] ?? null), $result->table->columns), escape: '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function report(string $key): ReportProvider
    {
        Gate::authorize('admin.access');

        return $this->reports->find($key) ?? abort(404);
    }

    private function period(Request $request): ReportPeriod
    {
        try {
            return ReportPeriod::fromPreset((string) $request->query('preset', '30d'), $request->query('start'), $request->query('end'));
        } catch (InvalidArgumentException $e) {
            throw ValidationException::withMessages(['period' => $e->getMessage()]);
        }
    }

    /** Chặn công thức khi mở bằng bảng tính (CSV injection). */
    private static function cell(string|int|float|bool|null $value): string
    {
        $text = match (true) {
            $value === null => '',
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };

        return is_string($value) && $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'".$text : $text;
    }
}
