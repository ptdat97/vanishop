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
use Modules\Shared\Contracts\PageCacheInvalidator;
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
        private readonly PluginSeeders $seeders,
        private readonly string $coreVersion,
        private readonly PluginDataGuard $dataGuard = new PluginDataGuard,
        private readonly ?PluginAssets $assets = null,
    ) {}

    public function install(string $pluginId): PluginRecord
    {
        $manifest = $this->manifestOrFail($pluginId);

        if (PluginRecord::query()->whereKey($pluginId)->exists()) {
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.plugin_plugin_id_already_installed', ['plugin_id' => $pluginId]));
        }

        $problems = $this->resolver->problemsFor($pluginId, $this->manifests->all(), $this->installedIds(), $this->coreVersion);
        if ($problems !== []) {
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.cannot_install_plugin_id', ['plugin_id' => $pluginId]), $problems);
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

            throw new PluginOperationFailed(__('extension::texts.plugin_manager.migration_plugin_id_failed_get', ['plugin_id' => $pluginId, 'get_message' => $exception->getMessage()]));
        }

        // Dữ liệu ban đầu sau migration. Lỗi → failed; sửa seeder rồi `vani:plugin:seed` (hoặc gỡ + cài lại).
        try {
            $seeded = $this->seeders->run($manifest);
        } catch (PluginOperationFailed $exception) {
            $record->update(['status' => PluginStatus::Failed, 'last_error' => $exception->getMessage()]);
            $this->rebuildCache();

            throw $exception;
        }

        $this->audit->record('extension.plugin.installed', 'plugin', $pluginId, ['version' => $manifest->version] + ($seeded === [] ? [] : ['seeded' => $seeded]));
        $this->rebuildCache();

        return $record->refresh();
    }

    public function enable(string $pluginId): void
    {
        $record = $this->recordOrFail($pluginId);

        if ($record->status === PluginStatus::Failed) {
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.plugin_plugin_id_failing_last', ['plugin_id' => $pluginId, 'last_error' => $record->last_error]));
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
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.cannot_enable_plugin_id', ['plugin_id' => $pluginId]), $problems);
        }

        $record->update(['status' => PluginStatus::Enabled]);
        if ($manifest !== null) {
            $this->assets?->publish($manifest);
        }

        $this->audit->record('extension.plugin.enabled', 'plugin', $pluginId);
        $this->afterStateChange();
    }

    /**
     * @param  bool  $force  tắt dù implementation còn việc dở dang (khẩn cấp: plugin lỗi) — ghi lý do vào audit
     */
    public function disable(string $pluginId, bool $force = false): void
    {
        $record = $this->recordOrFail($pluginId);

        $this->assertCanStop($pluginId, __('extension::texts.plugin_manager.disable'));

        $inUse = $this->required->inUse($pluginId);
        if ($inUse !== [] && ! $force) {
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.plugin_id_should_not_disabled', ['plugin_id' => $pluginId]).implode('; ', $inUse).__('extension::texts.plugin_manager.use_drain_stop_accepting_new'));
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
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.only_enabled_plugin_stopped_plugin', ['plugin_id' => $pluginId, 'status' => $record->status->value]));
        }
        $this->assertCanStop($pluginId, __('extension::texts.plugin_manager.stop'));

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
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.disable_plugin_id_before_uninstalling', ['plugin_id' => $pluginId]));
        }

        $dependents = $this->resolver->dependentsOf($pluginId, $this->manifests->all(), $this->installedIds());
        if ($dependents !== []) {
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.cannot_uninstall_plugin_id_plugins', ['plugin_id' => $pluginId]).implode(', ', $dependents).__('extension::texts.plugin_manager.depend'));
        }

        // Gỡ = không nạp provider nữa → IPN/webhook của giao dịch đang dở sẽ 404 (kể cả khi đã --force tắt).
        $inUse = $this->required->inUse($pluginId);
        if ($inUse !== []) {
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.cannot_uninstall_plugin_id', ['plugin_id' => $pluginId]).implode('; ', $inUse).'.');
        }

        $manifest = $this->manifests->find($pluginId);
        if ($purge && $manifest !== null) {
            $installed = array_intersect_key($this->manifests->all(), array_flip($this->installedIds()));
            $blockers = $this->dataGuard->purgeBlockers($manifest, $installed, $dropRetained);
            if ($blockers !== []) {
                throw new PluginOperationFailed(__('extension::texts.plugin_manager.cannot_delete_data_plugin_id', ['plugin_id' => $pluginId]).implode('; ', $blockers).__('extension::texts.plugin_manager.uninstall_without_purge_keep_data'));
            }
            $this->rollbackMigrations($manifest);
            $this->seeders->forget($pluginId);
        }

        $record->delete();
        $this->assets?->remove($pluginId);

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
            throw new PluginAlreadyCurrent(__('extension::texts.plugin_manager.plugin_plugin_id_already_version', ['plugin_id' => $pluginId, 'from' => $from]));
        }
        if (Comparator::lessThan($manifest->version, $from)) {
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.cannot_downgrade_plugin_id_from', ['plugin_id' => $pluginId, 'from' => $from, 'version' => $manifest->version]));
        }

        $others = array_values(array_diff($this->installedIds(), [$pluginId]));
        $problems = $this->resolver->problemsFor($pluginId, $this->manifests->all(), $others, $this->coreVersion);
        // Bản mới khai thêm capability: plugin đang chạy phải có sẵn nguồn, không thì nâng xong sẽ chạy thiếu.
        if (in_array($record->status, [PluginStatus::Enabled, PluginStatus::Draining], true)) {
            array_push($problems, ...$this->capabilityProblems($manifest, array_values(array_diff($this->enabledIds(), [$pluginId]))));
        }
        if ($problems !== []) {
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.cannot_upgrade_plugin_id_version', ['plugin_id' => $pluginId, 'version' => $manifest->version]), $problems);
        }

        try {
            $this->runMigrations($manifest);
        } catch (Throwable $exception) {
            $this->markFailed($pluginId, "upgrade {$from} → {$manifest->version}: {$exception->getMessage()}");

            throw new PluginOperationFailed(__('extension::texts.plugin_manager.migration_while_upgrading_plugin_id', ['plugin_id' => $pluginId, 'get_message' => $exception->getMessage()]));
        }
        // Bản mới thêm seeder → chỉ chạy seeder chưa chạy.
        try {
            $seeded = $this->seeders->run($manifest);
        } catch (PluginOperationFailed $exception) {
            $this->markFailed($pluginId, "upgrade {$from} → {$manifest->version}: {$exception->getMessage()}");

            throw $exception;
        }

        $record->update([
            'version' => $manifest->version,
            'status' => $record->status === PluginStatus::Failed ? PluginStatus::Installed : $record->status,
            'last_error' => null,
        ]);
        if (in_array($record->status, [PluginStatus::Enabled, PluginStatus::Draining], true)) {
            $this->assets?->publish($manifest);
        }
        $this->audit->record('extension.plugin.upgraded', 'plugin', $pluginId, ['from' => $from, 'to' => $manifest->version] + ($seeded === [] ? [] : ['seeded' => $seeded]));
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

    /**
     * Chạy seeder chưa chạy của plugin đã cài (`vani:plugin:seed`); `$rerun` chạy lại tất cả (seeder phải idempotent).
     * Plugin `failed` vì seeder: seed thành công → về `installed` (bật lại bằng enable).
     *
     * @return list<string> seeder đã chạy
     */
    public function seed(string $pluginId, bool $rerun = false): array
    {
        $record = $this->recordOrFail($pluginId);
        $manifest = $this->manifestOrFail($pluginId);

        $ran = $this->seeders->run($manifest, $rerun);
        if ($record->status === PluginStatus::Failed && str_contains((string) $record->last_error, 'Seeder [')) {
            $record->update(['status' => PluginStatus::Installed, 'last_error' => null]);
            $this->rebuildCache();
        }
        if ($ran !== []) {
            $this->audit->record('extension.plugin.seeded', 'plugin', $pluginId, ['seeders' => $ran, 'rerun' => $rerun]);
        }

        return $ran;
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

        foreach ($this->unloadable($loadable) as $id => $reason) {
            PluginRecord::query()->whereKey($id)->update(['status' => PluginStatus::Failed, 'last_error' => $reason]);
            $this->audit->record('extension.plugin.failed', 'plugin', $id, ['reason' => $reason, 'core' => $this->coreVersion]);
            unset($loadable[$id]);
        }

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

    /**
     * Plugin không được nạp dù đang bật: Core vừa nâng ra ngoài `requires.extension_api`/`requires.vanishop`
     * (`incompatible_extension_api`/`incompatible_core`), hoặc plugin
     * phụ thuộc của nó vừa bị loại (lan theo đồ thị). Deploy Core mới (`vani:plugin:cache` trong `optimize`) vì vậy không nạp
     * plugin viết cho Core cũ — plugin chuyển `failed` kèm lý do, Core vẫn chạy ([plugin-system §6](docs/05-plugin/plugin-system.md)).
     *
     * @param  array<string, PluginManifest>  $loadable
     * @return array<string, string> id → lý do
     */
    private function unloadable(array $loadable): array
    {
        $excluded = [];
        foreach ($loadable as $id => $manifest) {
            foreach ($this->resolver->problemsFor($id, $loadable, array_keys($loadable), $this->coreVersion) as $problem) {
                if (in_array($problem->code, ['incompatible_core', 'incompatible_extension_api'], true)) {
                    $excluded[$id] = "{$problem->code}: {$problem->message}";
                }
            }
        }

        do {
            $changed = false;
            foreach ($loadable as $id => $manifest) {
                if (isset($excluded[$id])) {
                    continue;
                }
                foreach (array_keys($manifest->requiresPlugins) as $dependency) {
                    if (isset($excluded[$dependency])) {
                        $excluded[$id] = __('extension::texts.plugin_manager.dependency_unavailable_dependency_plugin_dependency', ['dependency' => $dependency]);
                        $changed = true;
                        break;
                    }
                }
            }
        } while ($changed);

        return $excluded;
    }

    private function afterStateChange(): void
    {
        PluginActivation::forgetCache();
        $this->activation->flush();
        $this->rebuildCache();
        // Khối, slot, enricher của plugin xuất hiện/biến mất trên trang công khai: xoá ngay cache trang (ADR-051).
        app(PageCacheInvalidator::class)->flush();
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
        return $this->manifests->find($pluginId) ?? throw new PluginOperationFailed(__('extension::texts.plugin_manager.plugin_plugin_id_not_found', ['plugin_id' => $pluginId]));
    }

    private function recordOrFail(string $pluginId): PluginRecord
    {
        return PluginRecord::query()->find($pluginId) ?? throw new PluginOperationFailed(__('extension::texts.plugin_manager.plugin_plugin_id_not_installed', ['plugin_id' => $pluginId]));
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
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.cannot_verb_plugin_id_used', ['verb' => $verb, 'plugin_id' => $pluginId]).implode(', ', $dependents).'.');
        }

        $broken = $this->required->brokenWithout($pluginId, $this->enabledIds());
        if ($broken !== []) {
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.cannot_verb_plugin_id_last', ['verb' => $verb, 'plugin_id' => $pluginId]).implode(', ', $broken).__('extension::texts.plugin_manager.enable_replacement_plugin_first'));
        }

        $needed = $this->capabilities->brokenWithout($pluginId, $this->manifests->all(), $this->activeIds(), $this->enabledIds());
        if ($needed !== []) {
            throw new PluginOperationFailed(__('extension::texts.plugin_manager.cannot_verb_plugin_id_last_2', ['verb' => $verb, 'plugin_id' => $pluginId]).implode('; ', $needed).__('extension::texts.plugin_manager.enable_replacement_plugin_disable_dependent'));
        }
    }

    /**
     * @param  list<string>  $active
     * @return list<DependencyProblem>
     */
    private function capabilityProblems(PluginManifest $manifest, array $active): array
    {
        return array_map(
            fn (string $tag): DependencyProblem => new DependencyProblem($manifest->id, 'missing_capability', __('extension::texts.plugin_manager.requires_least_one_enabled_implementation').$this->capabilities->describe($tag).__('extension::texts.plugin_manager.enable_providing_plugin_first')),
            $this->capabilities->missingFor($manifest, $active),
        );
    }
}
