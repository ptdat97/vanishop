<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;

final class PluginSeedCommand extends Command
{
    protected $signature = 'vani:plugin:seed {plugin : Id plugin, ví dụ vani.vietqr}
        {--rerun : Chạy lại mọi seeder (seeder phải idempotent)}';

    protected $description = 'Chạy seeder chưa chạy của plugin đã cài (cài/nâng cấp tự chạy; dùng sau khi sửa seeder lỗi)';

    public function handle(PluginManager $plugins): int
    {
        try {
            $ran = $plugins->seed((string) $this->argument('plugin'), (bool) $this->option('rerun'));
        } catch (PluginOperationFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($ran === []) {
            $this->info('Không có seeder nào cần chạy.');
        }
        foreach ($ran as $seeder) {
            $this->line("  ✓ {$seeder}");
        }

        return self::SUCCESS;
    }
}
