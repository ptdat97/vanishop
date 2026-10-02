<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\Series;
use Modules\Extension\Contracts\Data\Table;

/**
 * Extension point (tag `vani.admin.dashboard.widgets`, ADR-030 §4.G, W6b): ô trên trang Tổng quan Admin.
 * Core kiểm tra `permission()`, gọi `render()` (lỗi → bỏ ô đó, ghi log kèm plugin) và vẽ theo kiểu dữ liệu trả về —
 * plugin không gửi HTML/Vue. Đọc dữ liệu qua contract đọc của Core (`OrderStatistics`, `OrderReader`…), không I/O mạng.
 */
interface DashboardWidget
{
    public const TAG = 'vani.admin.dashboard.widgets';

    /** Mã ổn định (snake_case), duy nhất trong các widget đang bật. */
    public function key(): string;

    public function label(): string;

    /** Quyền cần có để thấy widget; null = mọi nhân viên vào được Admin. */
    public function permission(): ?string;

    /** Độ rộng trên lưới 3 cột: 1, 2 hoặc 3. */
    public function width(): int;

    /** Thứ tự (nhỏ trước). */
    public function order(): int;

    public function render(): Metric|Series|Table;
}
