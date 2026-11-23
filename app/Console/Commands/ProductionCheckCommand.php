<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Observability\HealthCheck;
use Closure;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Modules\Extension\Application\Plugins\PluginDoctor;
use Throwable;

final class ProductionCheckCommand extends Command
{
    protected $signature = 'vani:production:check
        {--runtime : Kiểm tra thêm scheduler, outbox và Horizon sau khi khởi động worker}
        {--json : In kết quả dạng JSON}';

    protected $description = 'Kiểm tra mức sẵn sàng kỹ thuật của bản phát hành production';

    public function handle(PluginDoctor $doctor, HealthCheck $health): int
    {
        $runtime = (bool) $this->option('runtime');
        $checks = [
            'security' => $this->probe(fn (): array => $this->callSilently('vani:security:check') === self::SUCCESS
                ? $this->passed()
                : ['status' => 'fail', 'detail' => 'Chạy vani:security:check để xem lỗi cấu hình.']),
            'plugins' => $this->probe(fn (): array => $this->plugins($doctor)),
            'assets' => $this->probe($this->assets(...)),
        ];

        foreach ($health->run()['checks'] as $name => $check) {
            if (! $runtime && in_array($name, ['integration_outbox', 'scheduler'], true)) {
                continue;
            }
            $status = match ($check['status']) {
                'ok' => 'ok',
                'degraded' => $name === 'integration_outbox' ? 'warning' : 'fail',
                default => 'fail',
            };
            $checks[$name] = ['status' => $status, 'detail' => $check['detail'] ?? ''];
        }

        if ($runtime) {
            $checks['horizon'] = $this->probe($this->horizon(...));
        }

        $statuses = array_column($checks, 'status');
        $status = match (true) {
            in_array('fail', $statuses, true) => 'fail',
            in_array('warning', $statuses, true) => 'warning',
            default => 'ok',
        };

        if ($this->option('json')) {
            $this->line(json_encode(['status' => $status, 'checks' => $checks], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(['Kiểm tra', 'Trạng thái', 'Chi tiết'], collect($checks)
                ->map(fn (array $check, string $name): array => [$name, $check['status'], $check['detail']])->values()->all());
        }

        return $status === 'fail' ? self::FAILURE : self::SUCCESS;
    }

    /** @return array{status: string, detail: string} */
    private function plugins(PluginDoctor $doctor): array
    {
        $issues = $doctor->diagnose();
        $blocking = array_filter($issues, fn (array $issue): bool => $issue['level'] === PluginDoctor::ERROR
            || in_array($issue['code'], ['upgrade_pending', 'migrations_pending', 'bundled_not_installed'], true));
        if ($blocking !== []) {
            $codes = array_map(fn (array $issue): string => "{$issue['plugin']}/{$issue['code']}", $blocking);

            return ['status' => 'fail', 'detail' => implode(', ', $codes).'. Xem vani:plugin:doctor.'];
        }

        return $issues === [] ? $this->passed() : ['status' => 'warning', 'detail' => 'Xem cảnh báo bằng vani:plugin:doctor.'];
    }

    /** @return array{status: string, detail: string} */
    private function assets(): array
    {
        if (Vite::isRunningHot()) {
            return ['status' => 'fail', 'detail' => 'Release có file hot của Vite.'];
        }
        $path = public_path('build/manifest.json');
        if (! File::isFile($path)) {
            return ['status' => 'fail', 'detail' => 'Thiếu public/build/manifest.json của artifact CI.'];
        }
        $manifest = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        foreach (['resources/js/admin.ts', 'custom/theme/vani-base/css/app.css', 'custom/theme/vani-base/js/app.js'] as $entry) {
            if (! is_string($manifest[$entry]['file'] ?? null)) {
                return ['status' => 'fail', 'detail' => "Manifest thiếu entry {$entry}."];
            }
        }
        foreach ($manifest as $chunk) {
            foreach ([$chunk['file'] ?? null, ...($chunk['css'] ?? []), ...($chunk['assets'] ?? [])] as $file) {
                if (! is_string($file) || ! File::isFile(public_path('build/'.$file))) {
                    return ['status' => 'fail', 'detail' => 'Artifact thiếu file được khai báo trong manifest.'];
                }
            }
        }

        return $this->passed();
    }

    /** @return array{status: string, detail: string} */
    private function horizon(): array
    {
        if (config('queue.default') !== 'redis') {
            return ['status' => 'fail', 'detail' => 'Horizon cần QUEUE_CONNECTION=redis.'];
        }

        return $this->callSilently('horizon:status') === self::SUCCESS
            ? $this->passed()
            : ['status' => 'fail', 'detail' => 'Horizon chưa chạy hoặc đang tạm dừng. Xem horizon:status.'];
    }

    /**
     * @param  Closure(): array{status: string, detail: string}  $check
     * @return array{status: string, detail: string}
     */
    private function probe(Closure $check): array
    {
        try {
            return $check();
        } catch (Throwable $exception) {
            return ['status' => 'fail', 'detail' => 'Không chạy được kiểm tra ('.class_basename($exception).').'];
        }
    }

    /** @return array{status: string, detail: string} */
    private function passed(): array
    {
        return ['status' => 'ok', 'detail' => ''];
    }
}
