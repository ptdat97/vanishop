<?php

declare(strict_types=1);

namespace App\Observability;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Extension\Application\Plugins\RequiredExtensions;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Throwable;

/**
 * Health check tổng hợp (GET /health, Phase 6): ok | degraded | fail theo từng thành phần. `fail` → HTTP 503 (LB rút
 * node); `degraded` → 200 nhưng giám sát cảnh báo.
 */
final class HealthCheck
{
    public const HEARTBEAT_KEY = 'vani:heartbeat:scheduler';

    private const OUTBOX_BACKLOG_DEGRADED = 1_000;

    private const SCHEDULER_STALE_SECONDS = 300;

    public function __construct(private readonly RequiredExtensions $required) {}

    /**
     * @return array{status: string, checks: array<string, array{status: string, detail?: string}>}
     */
    public function run(): array
    {
        $checks = [
            'database' => $this->check(function (): array {
                DB::select('select 1');

                return ['status' => 'ok'];
            }),
            'cache' => $this->check(function (): array {
                Cache::put('vani:health:probe', 1, 10);

                return Cache::get('vani:health:probe') === 1 ? ['status' => 'ok'] : ['status' => 'fail', 'detail' => 'không đọc lại được giá trị'];
            }),
            'required_extensions' => $this->check(function (): array {
                $enabled = PluginRecord::query()->where('status', PluginStatus::Enabled)->pluck('id')->all();
                $missing = $this->required->missing($enabled);

                return $missing === [] ? ['status' => 'ok'] : ['status' => 'fail', 'detail' => 'thiếu: '.implode(', ', $missing)];
            }),
            'integration_outbox' => $this->check(function (): array {
                $backlog = DB::table('integration_outbox')->whereIn('status', ['pending', 'processing'])->count();
                $failed = DB::table('integration_outbox')->whereIn('status', ['failed', 'dead'])->count();

                return [
                    'status' => $backlog > self::OUTBOX_BACKLOG_DEGRADED || $failed > 0 ? 'degraded' : 'ok',
                    'detail' => "tồn {$backlog}, lỗi/dead {$failed}",
                ];
            }),
            'scheduler' => $this->check(function (): array {
                $beat = Cache::get(self::HEARTBEAT_KEY);
                if (! is_int($beat)) {
                    return ['status' => 'degraded', 'detail' => 'chưa có nhịp (schedule:run chưa chạy?)'];
                }
                $age = time() - $beat;

                return $age > self::SCHEDULER_STALE_SECONDS ? ['status' => 'degraded', 'detail' => "nhịp cuối {$age}s trước"] : ['status' => 'ok'];
            }),
        ];

        $statuses = array_column($checks, 'status');

        return [
            'status' => in_array('fail', $statuses, true) ? 'fail' : (in_array('degraded', $statuses, true) ? 'degraded' : 'ok'),
            'checks' => $checks,
        ];
    }

    /**
     * @param  callable(): array{status: string, detail?: string}  $probe
     * @return array{status: string, detail?: string}
     */
    private function check(callable $probe): array
    {
        try {
            return $probe();
        } catch (Throwable $exception) {
            return ['status' => 'fail', 'detail' => class_basename($exception)];
        }
    }
}
