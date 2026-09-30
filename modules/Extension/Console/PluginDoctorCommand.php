<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginDoctor;

final class PluginDoctorCommand extends Command
{
    protected $signature = 'vani:plugin:doctor {--json : In kết quả dạng JSON}';

    protected $description = 'Chẩn đoán plugin: tương thích, phụ thuộc, version, migration, provider (mã thoát 1 nếu có lỗi)';

    public function handle(PluginDoctor $doctor): int
    {
        $issues = $doctor->diagnose();

        if ($this->option('json')) {
            $this->line((string) json_encode($issues, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        } elseif ($issues === []) {
            $this->info('Không phát hiện vấn đề.');
        } else {
            $this->table(['Plugin', 'Mức', 'Mã', 'Chi tiết'], array_map(fn (array $issue): array => array_values($issue), $issues));
        }

        return collect($issues)->contains('level', PluginDoctor::ERROR) ? self::FAILURE : self::SUCCESS;
    }
}
