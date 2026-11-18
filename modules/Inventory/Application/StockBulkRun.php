<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

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

    private const TTL_HOURS = 24;

    /**
     * @param  list<object>  $jobs  job dùng `Batchable`, mỗi job một lô
     */
    public function dispatch(string $label, array $jobs): string
    {
        // Bộ đếm tạo khi lô đầu ghi kết quả (record) — queue `sync` chạy hết lô ngay trong dispatch().
        return Bus::batch($jobs)->name($label)->allowFailures()->onQueue('default')->dispatch()->id;
    }

    /**
     * @param  array<string, int>  $counts
     */
    public static function record(string $batchId, array $counts): void
    {
        foreach (array_intersect_key($counts, array_flip(self::COUNTERS)) as $counter => $value) {
            if ($value > 0) {
                Cache::add(self::key($batchId, $counter), 0, now()->addHours(self::TTL_HOURS));
                Cache::increment(self::key($batchId, $counter), $value);
            }
        }
    }

    /**
     * @return array{id: string, label: string, progress: int, finished: bool, failed_jobs: int, counts: array<string, int>}|null
     */
    public function status(string $batchId): ?array
    {
        $batch = Bus::findBatch($batchId);
        if (! $batch instanceof Batch) {
            return null;
        }

        return [
            'id' => $batch->id,
            'label' => $batch->name,
            'progress' => $batch->progress(),
            'finished' => $batch->finished(),
            'failed_jobs' => $batch->failedJobs,
            'counts' => array_combine(self::COUNTERS, array_map(fn (string $counter): int => (int) Cache::get(self::key($batchId, $counter), 0), self::COUNTERS)),
        ];
    }

    private static function key(string $batchId, string $counter): string
    {
        return "inventory:bulk-run:{$batchId}:{$counter}";
    }
}
