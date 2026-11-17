<?php

declare(strict_types=1);

namespace Modules\Extension\Testing;

use Closure;
use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\Series;
use Modules\Extension\Contracts\Data\Table;

/**
 * Contract test cho DashboardWidget. Kiểm tra: khoá snake_case; nhãn; độ rộng 1–3; quyền null hoặc mã quyền; render trả
 * Metric/Series/Table (chỉ đọc, không lỗi khi chưa có dữ liệu).
 */
final class DashboardWidgetContract
{
    /**
     * @param  Closure(): DashboardWidget  $widget
     */
    public static function define(string $label, Closure $widget): void
    {
        describe("DashboardWidget contract: {$label}", function () use ($widget): void {
            it('khoá, nhãn, độ rộng, quyền; render được khi chưa có dữ liệu', function () use ($widget): void {
                expect($widget()->key())->toMatch('/^[a-z][a-z0-9_]*$/')
                    ->and($widget()->label())->not->toBe('')
                    ->and($widget()->width())->toBeIn([1, 2, 3])
                    ->and($widget()->order())->toBeInt();
                $permission = $widget()->permission();
                expect($permission === null || $permission !== '')->toBeTrue();
                // render(): Metric|Series|Table (kiểu native) — gọi được, không ném lỗi.
                expect($widget()->render())->toBeObject();
            });
        });
    }
}
