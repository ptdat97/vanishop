<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginLoader;
use Modules\Extension\Domain\Plugin\DependencyResolver;
use Modules\Extension\Persistence\Models\PluginRecord;

final class PluginListCommand extends Command
{
    protected $signature = 'vani:plugin:list';

    protected $description = 'Liệt kê plugin: phiên bản, trạng thái, tương thích';

    public function handle(ManifestRepository $manifests, DependencyResolver $resolver, PluginLoader $loader): int
    {
        $records = PluginRecord::query()->get()->keyBy('id');
        $installed = $records->keys()->all();
        $rows = [];

        foreach ($manifests->all() as $manifest) {
            $record = $records->get($manifest->id);
            $problems = $resolver->problemsFor($manifest->id, $manifests->all(), array_values(array_diff($installed, [$manifest->id])), (string) config('vanishop.version'));

            $rows[] = [
                $manifest->id,
                $manifest->version,
                $record?->status->value ?? 'discovered',
                $problems === [] ? 'ok' : implode('; ', array_map(fn ($p): string => $p->code, $problems)),
                $loader->failures()[$manifest->id] ?? $record?->last_error ?? '',
            ];
        }

        $this->table(['Plugin', 'Version', 'Status', 'Compat', 'Error'], $rows);

        foreach ($manifests->invalid() as $file => $error) {
            $this->warn("Manifest lỗi: {$error}");
        }

        return self::SUCCESS;
    }
}
