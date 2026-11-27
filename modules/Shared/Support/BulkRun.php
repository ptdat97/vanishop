<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

/**
 * Việc hàng loạt lớn chạy nền (tồn kho L20, nhập file sản phẩm): các lô là job `Batchable` trong một `Bus::batch` (bảng
 * `job_batches`, queue `default`). Mỗi lô cộng dồn bộ đếm và lỗi (tối đa MAX_ERRORS) vào cache; Admin hỏi tiến độ qua
 * `status()`.
 */
final class BulkRun
{
    public const MAX_ERRORS = 200;

    private const TTL_HOURS = 24;

    /**
     * @param  list<object>  $jobs  job dùng `Batchable`, mỗi job một lô
     */
    public static function dispatch(string $label, array $jobs): string
    {
        // Bộ đếm tạo khi lô đầu ghi kết quả (record) — queue `sync` chạy hết lô ngay trong dispatch().
        return Bus::batch($jobs)->name($label)->allowFailures()->onQueue('default')->dispatch()->id;
    }

    /**
     * @param  array<string, int>  $counts
     * @param  list<string>  $errors
     */
    public static function record(string $batchId, array $counts, array $errors = []): void
    {
        foreach ($counts as $counter => $value) {
            if ($value > 0) {
                DurableCache::store()->add(self::key($batchId, $counter), 0, now()->addHours(self::TTL_HOURS));
                DurableCache::store()->increment(self::key($batchId, $counter), $value);
            }
        }
        if ($errors !== []) {
            $lock = CacheLocks::lock(DurableCache::store(), self::key($batchId, 'errors-lock'), 10);
            $lock->block(5, function () use ($batchId, $errors): void {
                $current = (array) DurableCache::store()->get(self::key($batchId, 'errors'), []);
                DurableCache::store()->put(self::key($batchId, 'errors'), array_slice([...$current, ...$errors], 0, self::MAX_ERRORS), now()->addHours(self::TTL_HOURS));
            });
        }
    }

    /**
     * @param  list<string>  $counters
     * @return array{id: string, label: string, progress: int, finished: bool, failed_jobs: int, counts: array<string, int>, errors: list<string>}|null
     */
    public static function status(string $batchId, array $counters): ?array
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
            'counts' => array_combine($counters, array_map(fn (string $counter): int => (int) DurableCache::store()->get(self::key($batchId, $counter), 0), $counters)),
            'errors' => array_values(array_map('strval', (array) DurableCache::store()->get(self::key($batchId, 'errors'), []))),
        ];
    }

    private static function key(string $batchId, string $counter): string
    {
        return "bulk-run:{$batchId}:{$counter}";
    }
}
