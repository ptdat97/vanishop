<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;

final class PluginDisableCommand extends Command
{
    protected $signature = 'vani:plugin:disable {plugin} {--scope= : Bỏ trống để tắt hoàn toàn; hoặc owner | brand:<id> | channel:<id>}';

    protected $description = 'Tắt plugin ở một phạm vi hoặc tắt hoàn toàn';

    public function handle(PluginManager $plugins): int
    {
        try {
            $scope = $this->option('scope');
            [$type, $id] = $scope === null ? [null, null] : ScopeOption::parse((string) $scope);
            $plugins->disable((string) $this->argument('plugin'), $type, $id);
        } catch (PluginOperationFailed $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Đã tắt '.$this->argument('plugin').'.');

        return self::SUCCESS;
    }
}
