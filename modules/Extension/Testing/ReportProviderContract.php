<?php

declare(strict_types=1);

namespace Modules\Extension\Testing;

use Closure;
use DateTimeImmutable;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\ReportProvider;

/**
 * Contract test cho ReportProvider. Kiểm tra: khoá dùng được trong URL; nhãn, mô tả; quyền null hoặc mã quyền; chạy được
 * trên khoảng thời gian không có dữ liệu và kết quả xác định (chỉ đọc).
 */
final class ReportProviderContract
{
    /**
     * @param  Closure(): ReportProvider  $report
     */
    public static function define(string $label, Closure $report): void
    {
        describe("ReportProvider contract: {$label}", function () use ($report): void {
            it('khoá, nhãn, mô tả, quyền', function () use ($report): void {
                expect($report()->key())->toMatch('/^[a-z][a-z0-9_-]*$/')
                    ->and($report()->label())->not->toBe('')
                    ->and($report()->description())->not->toBe('');
                $permission = $report()->permission();
                expect($permission === null || $permission !== '')->toBeTrue();
            });

            it('chạy được, kết quả xác định', function () use ($report): void {
                $period = new ReportPeriod(new DateTimeImmutable('2020-01-01 00:00:00'), new DateTimeImmutable('2020-01-07 23:59:59'));
                $result = $report()->run($period);
                expect($result)->toBeInstanceOf(ReportResult::class)
                    ->and($report()->run($period)->toArray())->toBe($result->toArray());
            });
        });
    }
}
