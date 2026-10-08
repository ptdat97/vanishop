<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Composer\Semver\Comparator;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Modules\Extension\Domain\Plugin\DependencyProblem;
use Modules\Extension\Domain\Plugin\DependencyResolver;
use Modules\Extension\Domain\Plugin\PluginManifest;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Identity\Contracts\AuditLogger;
use Throwable;

/**
 * Vòng đời plugin: install → enable/disable → uninstall.
 *
 * @see docs/05-plugin/plugin-system.md §4–§7
 */
final class PluginManager
{
    public function __construct(
        private readonly ManifestRepository $manifests,
        private readonly DependencyResolver $resolver,
        private readonly PluginStateCache $cache,
        private readonly PluginActivation $activation,
        private readonly AuditLogger $audit,
        private readonly ConsoleKernel $console,
        private readonly RequiredExtensions $required,
        private readonly PluginCapabilities $capabilities,
        private readonly string $coreVersion,
        private readonly PluginDataGuard $dataGuard = new PluginDataGuard,
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

    public function enable(string $pluginId): void
    {
        $record = $this->recordOrFail($pluginId);

        if ($record->status === PluginStatus::Failed) {
            throw new PluginOperationFailed("Plugin [{$pluginId}] đang lỗi: {$record->last_error}");
        }

        $active = array_values(array_diff($this->enabledIds(), [$pluginId]));
        $problems = array_values(array_filter(
            $this->resolver->problemsFor($pluginId, $this->manifests->all(), $active, $this->coreVersion),
            fn ($problem): bool => $problem->code !== 'not_found',
        ));
        $manifest = $this->manifests->find($pluginId);
        if ($manifest !== null) {
            array_push($problems, ...$this->capabilityProblems($manifest, $active));
        }
        if ($problems !== []) {
            throw new PluginOperationFailed("Không thể bật [{$pluginId}]:", $problems);
        }

        $record->update(['status' => PluginStatus::Enabled]);

        $this->audit->record('extension.plugin.enabled', 'plugin', $pluginId);
        $this->afterStateChange();
    }

    /**
     * @param  bool  $force  tắt dù implementation còn việc dở dang (khẩn cấp: plugin lỗi) — ghi lý do vào audit
     */
    public function disable(string $pluginId, bool $force = false): void
    {
        $record = $this->recordOrFail($pluginId);

        $this->assertCanStop($pluginId, 'tắt');

        $inUse = $this->required->inUse($pluginId);
        if ($inUse !== [] && ! $force) {
            throw new PluginOperationFailed("Không nên tắt [{$pluginId}] lúc này: ".implode('; ', $inUse).'. Dùng --drain để ngừng nhận giao dịch mới và tự tắt khi xử lý xong, hoặc --force (việc dở dang sẽ không được ghi nhận tự động).');
        }

        $record->update(['status' => PluginStatus::Disabled]);

        $this->audit->record('extension.plugin.disabled', 'plugin', $pluginId, $inUse === [] ? [] : ['forced' => true, 'in_use' => $inUse]);
        $this->afterStateChange();
    }

    /**
     * Ngừng plugin có việc dở dang (0.3.23): `draining` — không nhận giao dịch mới, vẫn xử lý giao dịch đang dở; tự tắt
     * khi hết (finishDraining, lịch 5 phút). Không có việc dở dang → tắt ngay.
     *
     * @return bool true = đã tắt ngay; false = đang draining
     */
    public function drain(string $pluginId): bool
    {
        $record = $this->recordOrFail($pluginId);
        if ($record->status !== PluginStatus::Enabled) {
            throw new PluginOperationFailed("Chỉ ngừng được plugin đang bật — [{$pluginId}] đang {$record->status->value}.");
        }
        $this->assertCanStop($pluginId, 'ngừng');

        $inUse = $this->required->inUse($pluginId);
        if ($inUse === []) {
            $this->disable($pluginId);

            return true;
        }

        $record->update(['status' => PluginStatus::Draining]);
        $this->audit->record('extension.plugin.draining', 'plugin', $pluginId, ['in_use' => $inUse]);
        $this->afterStateChange();

        return false;
    }

    /**
     * Tắt các plugin draining đã hết việc dở dang.
     *
     * @return list<string> plugin đã tắt
     */
    public function finishDraining(): array
    {
        $finished = [];
        foreach (PluginRecord::query()->where('status', PluginStatus::Draining)->orderBy('id')->get() as $record) {
            if ($this->required->inUse((string) $record->id) !== []) {
                continue;
            }
            $record->update(['status' => PluginStatus::Disabled]);
            $this->audit->record('extension.plugin.drained', 'plugin', (string) $record->id);
            $finished[] = (string) $record->id;
        }
        if ($finished !== []) {
            $this->afterStateChange();
        }

        return $finished;
    }

    /**
     * Việc dở dang của plugin (thanh toán chờ, vận đơn đang giao…).
     *
     * @return list<string>
     */
    public function inUse(string $pluginId): array
    {
        return $this->required->inUse($pluginId);
    }

    /**
     * @param  bool  $purge  rollback migration, xoá bảng owned — chặn khi còn tham chiếu/khoá ngoại/dữ liệu lưu giữ
     * @param  bool  $dropRetained  cho phép xoá bảng `data.retained` khi purge (đã xuất/lưu trữ)
     */
    public function uninstall(string $pluginId, bool $purge = false, bool $dropRetained = false): void
    {
        $record = $this->recordOrFail($pluginId);

        if (in_array($record->status, [PluginStatus::Enabled, PluginStatus::Draining], true)) {
            throw new PluginOperationFailed("Hãy tắt [{$pluginId}] trước khi gỡ.");
        }

        $dependents = $this->resolver->dependentsOf($pluginId, $this->manifests->all(), $this->installedIds());
        if ($dependents !== []) {
            throw new PluginOperationFailed("Không thể gỡ [{$pluginId}]: các plugin ".implode(', ', $dependents).' phụ thuộc vào nó.');
        }

        // Gỡ = không nạp provider nữa → IPN/webhook của giao dịch đang dở sẽ 404 (kể cả khi đã --force tắt).
        $inUse = $this->required->inUse($pluginId);
        if ($inUse !== []) {
            throw new PluginOperationFailed("Không thể gỡ [{$pluginId}]: ".implode('; ', $inUse).'.');
        }

        $manifest = $this->manifests->find($pluginId);
        if ($purge && $manifest !== null) {
            $installed = array_intersect_key($this->manifests->all(), array_flip($this->installedIds()));
            $blockers = $this->dataGuard->purgeBlockers($manifest, $installed, $dropRetained);
            if ($blockers !== []) {
                throw new PluginOperationFailed("Không thể xoá dữ liệu của [{$pluginId}]: ".implode('; ', $blockers).'. Gỡ không --purge để giữ dữ liệu.');
            }
            $this->rollbackMigrations($manifest);
        }

        $record->delete();

        $this->audit->record('extension.plugin.uninstalled', 'plugin', $pluginId, ['purge' => $purge] + ($purge && $dropRetained ? ['dropped_retained' => $manifest?->data->retained ?? []] : []));
        $this->afterStateChange();
    }

    /**
     * Nâng plugin đã cài lên version trong manifest hiện tại: chỉ nâng (không hạ), kiểm tra tương thích Core và
     * phụ thuộc, chạy migration chưa chạy (phải expand/contract — tương thích ngược). Plugin `failed` nâng thành công
     * chuyển về `installed` (bật lại bằng enable).
     */
    public function upgrade(string $pluginId): PluginRecord
    {
        $record = $this->recordOrFail($pluginId);
        $manifest = $this->manifestOrFail($pluginId);
        $from = $record->version;

        if (Comparator::equalTo($manifest->version, $from)) {
            throw new PluginOperationFailed("Plugin [{$pluginId}] đã ở version {$from}.");
        }
        if (Comparator::lessThan($manifest->version, $from)) {
            throw new PluginOperationFailed("Không hạ version [{$pluginId}] từ {$from} xuống {$manifest->version}.");
        }

        $others = array_values(array_diff($this->installedIds(), [$pluginId]));
        $problems = $this->resolver->problemsFor($pluginId, $this->manifests->all(), $others, $this->coreVersion);
        // Bản mới khai thêm capability: plugin đang chạy phải có sẵn nguồn, không thì nâng xong sẽ chạy thiếu.
        if (in_array($record->status, [PluginStatus::Enabled, PluginStatus::Draining], true)) {
            array_push($problems, ...$this->capabilityProblems($manifest, array_values(array_diff($this->enabledIds(), [$pluginId]))));
        }
        if ($problems !== []) {
            throw new PluginOperationFailed("Không thể nâng [{$pluginId}] lên {$manifest->version}:", $problems);
        }

        try {
            $this->runMigrations($manifest);
        } catch (Throwable $exception) {
            $this->markFailed($pluginId, "upgrade {$from} → {$manifest->version}: {$exception->getMessage()}");

            throw new PluginOperationFailed("Migration khi nâng [{$pluginId}] lỗi: {$exception->getMessage()}");
        }

        $record->update([
            'version' => $manifest->version,
            'status' => $record->status === PluginStatus::Failed ? PluginStatus::Installed : $record->status,
            'last_error' => null,
        ]);
        $this->audit->record('extension.plugin.upgraded', 'plugin', $pluginId, ['from' => $from, 'to' => $manifest->version]);
        $this->afterStateChange();

        return $record->refresh();
    }

    /**
     * Cài + bật các plugin hệ thống (`"bundled": true`) còn thiếu, theo thứ tự phụ thuộc — dùng bởi `vani:install`.
     * Idempotent: plugin đã bật thì bỏ qua; plugin người dùng đã tắt có chủ đích **vẫn được giữ tắt**.
     *
     * @return array{installed: list<string>, enabled: list<string>}
     */
    public function installBundled(): array
    {
        $bundled = array_filter($this->manifests->all(), fn (PluginManifest $manifest): bool => $manifest->bundled);
        $result = ['installed' => [], 'enabled' => []];

        foreach ($this->resolver->loadOrder($bundled) as $id) {
            if (PluginRecord::query()->whereKey($id)->exists()) {
                continue;
            }

            $this->install($id);
            $this->enable($id);
            $result['installed'][] = $id;
            $result['enabled'][] = $id;
        }

        return $result;
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
        PluginActivation::forgetCache();
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

    /**
     * Đang chạy (bật hoặc draining) — plugin phụ thuộc đang draining vẫn cần plugin nền.
     *
     * @return list<string>
     */
    private function activeIds(): array
    {
        return PluginRecord::query()->whereIn('status', [PluginStatus::Enabled, PluginStatus::Draining])->pluck('id')->all();
    }

    /**
     * Không bị plugin khác phụ thuộc; không phải implementation cuối của extension point bắt buộc (plugin draining không
     * còn nhận giao dịch mới nên không tính là implementation thay thế).
     */
    private function assertCanStop(string $pluginId, string $verb): void
    {
        $dependents = array_values(array_diff($this->resolver->dependentsOf($pluginId, $this->manifests->all(), $this->activeIds()), [$pluginId]));
        if ($dependents !== []) {
            throw new PluginOperationFailed("Không thể {$verb} [{$pluginId}]: đang được dùng bởi ".implode(', ', $dependents).'.');
        }

        $broken = $this->required->brokenWithout($pluginId, $this->enabledIds());
        if ($broken !== []) {
            throw new PluginOperationFailed("Không thể {$verb} [{$pluginId}]: đây là implementation cuối cùng của ".implode(', ', $broken).' — bật plugin thay thế trước.');
        }

        $needed = $this->capabilities->brokenWithout($pluginId, $this->manifests->all(), $this->activeIds(), $this->enabledIds());
        if ($needed !== []) {
            throw new PluginOperationFailed("Không thể {$verb} [{$pluginId}]: là nguồn cuối cùng của capability mà plugin khác cần (".implode('; ', $needed).') — bật plugin thay thế hoặc tắt plugin cần trước.');
        }
    }

    /**
     * @param  list<string>  $active
     * @return list<DependencyProblem>
     */
    private function capabilityProblems(PluginManifest $manifest, array $active): array
    {
        return array_map(
            fn (string $tag): DependencyProblem => new DependencyProblem($manifest->id, 'missing_capability', 'Cần ít nhất một implementation đang bật của '.$this->capabilities->describe($tag).' — bật plugin cung cấp trước.'),
            $this->capabilities->missingFor($manifest, $active),
        );
    }
}
