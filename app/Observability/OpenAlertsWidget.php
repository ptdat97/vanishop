<?php

declare(strict_types=1);

namespace App\Observability;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Table;

/**
 * Ô "Cảnh báo vận hành" trên trang Tổng quan: các cảnh báo đang mở (alert_states), mức nặng trước.
 */
final class OpenAlertsWidget implements DashboardWidget
{
    private const LEVELS = ['critical' => 'Khẩn', 'high' => 'Cao', 'normal' => 'Thường'];

    public function key(): string
    {
        return 'open_alerts';
    }

    public function label(): string
    {
        return 'Cảnh báo vận hành';
    }

    public function permission(): string
    {
        return 'system.monitor';
    }

    public function width(): int
    {
        return 3;
    }

    public function order(): int
    {
        return 0;
    }

    public function render(): Table
    {
        $rows = DB::table('alert_states')->where('status', 'firing')->get()
            ->sortBy(fn (object $row): int => array_search($row->severity, array_keys(self::LEVELS), true) ?: 0)
            ->map(fn (object $row): array => [
                'level' => self::LEVELS[$row->severity] ?? $row->severity,
                'title' => $row->title,
                'detail' => $row->detail,
                'since' => Carbon::parse($row->fired_at)->timezone('Asia/Ho_Chi_Minh')->format('d/m H:i'),
            ])->values()->all();

        return new Table(
            [new Column('level', 'Mức'), new Column('title', 'Cảnh báo'), new Column('detail', 'Chi tiết'), new Column('since', 'Từ')],
            $rows,
            $rows === [] ? 'Không có cảnh báo đang mở.' : '',
        );
    }
}
