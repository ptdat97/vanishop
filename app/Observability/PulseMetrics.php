<?php

declare(strict_types=1);

namespace App\Observability;

use Illuminate\Support\Facades\Log;
use Laravel\Pulse\Facades\Pulse;
use Modules\Shared\Contracts\Metrics;
use Throwable;

/**
 * Metric ghi vào Pulse: counter → Pulse::record (cộng `sum` theo khoảng thời gian), gauge → Pulse::set (giá trị mới
 * nhất). Kiểu Pulse = `vani.<tên>`. Pulse tắt (PULSE_ENABLED=false) thì Pulse tự bỏ qua.
 */
final class PulseMetrics implements Metrics
{
    public const PREFIX = 'vani.';

    public function increment(string $name, int $by = 1, string $key = 'all'): void
    {
        if ($by === 0) {
            return;
        }

        $this->safely(fn () => Pulse::record(self::PREFIX.$name, $key, $by)->sum()->count());
    }

    public function gauge(string $name, int $value, string $key = 'all'): void
    {
        $this->safely(fn () => Pulse::set(self::PREFIX.$name, $key, (string) $value));
    }

    private function safely(callable $record): void
    {
        try {
            $record();
        } catch (Throwable $exception) {
            Log::debug('Không ghi được metric.', ['error' => $exception->getMessage()]);
        }
    }
}
