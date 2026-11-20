<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Shared\Support\BulkRun;

/**
 * Việc tồn kho hàng loạt lớn chạy nền (backlog L20, Owner 2026-11-19): nhập file tồn và sửa hàng loạt quá `SYNC_LIMIT`
 * dòng/biến thể được chia lô `CHUNK` thành job trong một `Bus::batch` (bảng `job_batches`, queue `default`) — không còn
 * trần 5.000. Admin xem tiến độ + kết quả (số dòng ghi, xung đột, bỏ qua) qua `status()`; bộ đếm cộng dồn ở cache.
 */
final class StockBulkRun
{
    /** Tới mức này vẫn chạy ngay trong request (nhanh, có kết quả liền). */
    public const SYNC_LIMIT = 1000;

    public const CHUNK = 500;

    private const COUNTERS = ['updated', 'unchanged', 'skipped', 'conflicts'];

    /**
     * @param  list<object>  $jobs  job dùng `Batchable`, mỗi job một lô
     */
    public function dispatch(string $label, array $jobs): string
    {
        return BulkRun::dispatch($label, $jobs);
    }

    /**
     * @param  array<string, int>  $counts
     */
    public static function record(string $batchId, array $counts): void
    {
        BulkRun::record($batchId, array_intersect_key($counts, array_flip(self::COUNTERS)));
    }

    /**
     * @return array{id: string, label: string, progress: int, finished: bool, failed_jobs: int, counts: array<string, int>, errors: list<string>}|null
     */
    public function status(string $batchId): ?array
    {
        return BulkRun::status($batchId, self::COUNTERS);
    }
}
