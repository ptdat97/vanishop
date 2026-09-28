<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Hooks\HookRegistry;

final class PluginHooksCommand extends Command
{
    protected $signature = 'vani:plugin:hooks {plugin? : Chỉ hiển thị hook mà plugin này nghe}';

    protected $description = 'Liệt kê hook đã khai báo và listener (Core/plugin)';

    public function handle(HookRegistry $registry, HookManager $hooks): int
    {
        $owners = $hooks->listenerOwners();
        $filter = $this->argument('plugin');
        $rows = [];

        foreach ($registry->all() as $name => $definition) {
            $listeners = array_map(fn (?string $owner): string => $owner ?? 'core', $owners[$name] ?? []);

            if ($filter !== null && ! in_array($filter, $listeners, true)) {
                continue;
            }

            $rows[] = [$name, $definition->type->value, $definition->public ? 'public' : 'internal', $definition->since, implode(', ', $listeners) ?: '-'];
        }

        $this->table(['Hook', 'Type', 'Visibility', 'Since', 'Listeners'], $rows);

        return self::SUCCESS;
    }
}
