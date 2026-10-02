<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;

/**
 * Extension point (tag `vani.admin.reports`, ADR-030 §4.G, W6b): báo cáo trong Admin → Báo cáo.
 * Core dựng trang (chọn khoảng thời gian, bảng, biểu đồ, tổng), kiểm tra quyền và xuất CSV; plugin chỉ trả dữ liệu.
 * Đọc qua contract đọc của Core (`OrderStatistics`…); không ghi, không I/O mạng đồng bộ.
 */
interface ReportProvider
{
    public const TAG = 'vani.admin.reports';

    /** Mã ổn định (kebab-case hoặc snake_case) — xuất hiện trong URL `/{admin}/reports/{key}`. */
    public function key(): string;

    public function label(): string;

    public function description(): string;

    /** Quyền cần có; null = mọi nhân viên vào được Admin. */
    public function permission(): ?string;

    public function run(ReportPeriod $period): ReportResult;
}
