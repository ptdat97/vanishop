<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\DB;
use Modules\Extension\Domain\Plugin\DependencyResolver;
use Modules\Extension\Domain\Plugin\PluginManifest;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Extension\Persistence\Models\PluginScopeRecord;
use Modules\Identity\Contracts\AuditLogger;
use Throwable;

/**
 * Vòng đời plugin: install → enable/disable (theo scope) → uninstall.
 *
 * @see docs/05-plugin/plugin-system.md §4–§7
 */
final class PluginManager
{
    public const SCOPE_TYPES = ['owner', 'brand', 'channel'];

    public function __construct(
        private readonly ManifestRepository $manifests,
        private readonly DependencyResolver $resolver,
        private readonly PluginStateCache $cache,
        private readonly PluginActivation $activation,
        private readonly AuditLogger $audit,
        private readonly ConsoleKernel $console,
        private readonly string $coreVersion,
    ) {}

    public function install(string $pluginId): PluginRecord
    {
        $manifest = $this->manifestOrFail($pluginId);

        if (PluginRecord::query()->whereKey($pluginId)->exists()) {
            throw new PluginOperationFailed("Plugin [{$pluginId}] đã được cài.");
        }

        $problems = $this->resolver->problemsFor($pluginId, $this->manifests->all(), $this->installedIds(), $this->coreVersion);
        if ($problems !== []) {
            throw new PluginOperationFailed("Không thể cài [{$pluginId}]:", $problems);
        }

        $record = PluginRecord::query()->create([
            'id' => $pluginId,
            'version' => $manifest->version,
            'status' => PluginStatus::Installed,
            'installed_at' => now(),
        ]);

        try {
            $this->runMigrations($manifest);
        } catch (Throwable $exception) {
            $record->update(['status' => PluginStatus::Failed, 'last_error' => $exception->getMessage()]);
            $this->rebuildCache();

            throw new PluginOperationFailed("Migration của [{$pluginId}] lỗi: {$exception->getMessage()}");
        }

        $this->audit->record('extension.plugin.installed', 'plugin', $pluginId, ['version' => $manifest->version]);
        $this->rebuildCache();

        return $record->refresh();
    }

    public function enable(string $pluginId, string $scopeType = 'owner', ?int $scopeId = null): void
    {
        $record = $this->recordOrFail($pluginId);
        $this->assertScope($pluginId, $scopeType, $scopeId);

        if ($record->status === PluginStatus::Failed) {
            throw new PluginOperationFailed("Plugin [{$pluginId}] đang lỗi: {$record->last_error}");
        }

        $active = array_values(array_diff($this->enabledIds(), [$pluginId]));
        $problems = array_values(array_filter(
            $this->resolver->problemsFor($pluginId, $this->manifests->all(), $active, $this->coreVersion),
            fn ($problem): bool => $problem->code !== 'not_found',
        ));
        if ($problems !== []) {
            throw new PluginOperationFailed("Không thể bật [{$pluginId}]:", $problems);
        }

        DB::transaction(function () use ($record, $scopeType, $scopeId): void {
            PluginScopeRecord::query()->updateOrCreate(
                ['plugin_id' => $record->id, 'scope_type' => $scopeType, 'scope_id' => $scopeId],
                ['enabled' => true],
            );
            $record->update(['status' => PluginStatus::Enabled]);
        });

        $this->audit->record('extension.plugin.enabled', 'plugin', $pluginId, ['scope_type' => $scopeType, 'scope_id' => $scopeId]);
        $this->afterStateChange();
    }

    /**
     * Tắt ở một scope, hoặc tắt hoàn toàn khi không truyền scope.
     */
    public function disable(string $pluginId, ?string $scopeType = null, ?int $scopeId = null): void
    {
        $record = $this->recordOrFail($pluginId);

        if ($scopeType === null) {
            $dependents = $this->resolver->dependentsOf($pluginId, $this->manifests->all(), $this->enabledIds());
            if ($dependents !== []) {
                throw new PluginOperationFailed("Không thể tắt [{$pluginId}]: đang được dùng bởi ".implode(', ', $dependents).'.');
            }
        }

        DB::transaction(function () use ($record, $scopeType, $scopeId): void {
            $scopes = $record->scopes();
            if ($scopeType !== null) {
                $scopes->where('scope_type', $scopeType)->where('scope_id', $scopeId);
            }
            $scopes->update(['enabled' => false]);

            $stillEnabled = $record->scopes()->where('enabled', true)->exists();
            $record->update(['status' => $stillEnabled ? PluginStatus::Enabled : PluginStatus::Disabled]);
        });

        $this->audit->record('extension.plugin.disabled', 'plugin', $pluginId, ['scope_type' => $scopeType, 'scope_id' => $scopeId]);
        $this->afterStateChange();
    }

    public function uninstall(string $pluginId, bool $purge = false): void
    {
        $record = $this->recordOrFail($pluginId);

        if ($record->status === PluginStatus::Enabled) {
            throw new PluginOperationFailed("Hãy tắt [{$pluginId}] trước khi gỡ.");
        }

        $dependents = $this->resolver->dependentsOf($pluginId, $this->manifests->all(), $this->installedIds());
        if ($dependents !== []) {
            throw new PluginOperationFailed("Không thể gỡ [{$pluginId}]: các plugin ".implode(', ', $dependents).' phụ thuộc vào nó.');
        }

        $manifest = $this->manifests->find($pluginId);
        if ($purge && $manifest !== null) {
            $this->rollbackMigrations($manifest);
        }

        $record->delete();

        $this->audit->record('extension.plugin.uninstalled', 'plugin', $pluginId, ['purge' => $purge]);
        $this->afterStateChange();
    }

    public function markFailed(string $pluginId, string $error): void
    {
        PluginRecord::query()->whereKey($pluginId)->update(['status' => PluginStatus::Failed, 'last_error' => $error]);
        $this->afterStateChange();
    }

    /**
     * Ghi lại file cache nạp plugin theo thứ tự phụ thuộc.
     */
    public function rebuildCache(): void
    {
        $manifests = $this->manifests->all();
        $records = PluginRecord::query()->get()->keyBy('id');

        $loadable = array_filter(
            $manifests,
            fn (PluginManifest $manifest): bool => $records->has($manifest->id) && $records[$manifest->id]->status->loadsProvider(),
        );

        $entries = [];
        foreach ($this->resolver->loadOrder($loadable) as $id) {
            $entries[] = [
                'id' => $id,
                'provider' => $loadable[$id]->provider,
                'path' => $loadable[$id]->path,
                'status' => $records[$id]->status->value,
            ];
        }

        $this->cache->write($entries);
    }

    private function afterStateChange(): void
    {
        $this->activation->flush();
        $this->rebuildCache();
    }

    private function runMigrations(PluginManifest $manifest): void
    {
        $path = $manifest->path.'/Database/migrations';
        if (is_dir($path)) {
            $this->console->call('migrate', ['--path' => $path, '--realpath' => true, '--force' => true]);
        }
    }

    private function rollbackMigrations(PluginManifest $manifest): void
    {
        $path = $manifest->path.'/Database/migrations';
        if (is_dir($path)) {
            $this->console->call('migrate:rollback', ['--path' => $path, '--realpath' => true, '--force' => true]);
        }
    }

    private function assertScope(string $pluginId, string $scopeType, ?int $scopeId): void
    {
        if (! in_array($scopeType, self::SCOPE_TYPES, true)) {
            throw new PluginOperationFailed("Scope [{$scopeType}] không hợp lệ (".implode(', ', self::SCOPE_TYPES).').');
        }

        if (($scopeType === 'owner') !== ($scopeId === null)) {
            throw new PluginOperationFailed('Scope owner không có id; các scope khác bắt buộc có id.');
        }

        $manifest = $this->manifestOrFail($pluginId);
        if (! in_array($scopeType, $manifest->scopes, true) && $scopeType !== 'owner') {
            throw new PluginOperationFailed("Plugin [{$pluginId}] không hỗ trợ scope [{$scopeType}].");
        }
    }

    private function manifestOrFail(string $pluginId): PluginManifest
    {
        return $this->manifests->find($pluginId) ?? throw new PluginOperationFailed("Không tìm thấy plugin [{$pluginId}] trong custom/plugin.");
    }

    private function recordOrFail(string $pluginId): PluginRecord
    {
        return PluginRecord::query()->find($pluginId) ?? throw new PluginOperationFailed("Plugin [{$pluginId}] chưa được cài.");
    }

    /**
     * @return list<string>
     */
    private function installedIds(): array
    {
        return PluginRecord::query()->where('status', '!=', PluginStatus::Failed)->pluck('id')->all();
    }

    /**
     * @return list<string>
     */
    private function enabledIds(): array
    {
        return PluginRecord::query()->where('status', PluginStatus::Enabled)->pluck('id')->all();
    }
}
