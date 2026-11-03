<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Composer\Semver\Comparator;
use Illuminate\Support\Collection;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Domain\Plugin\DependencyProblem;
use Modules\Extension\Domain\Plugin\DependencyResolver;
use Modules\Extension\Domain\Plugin\PluginManifest;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Modules\Tenancy\Contracts\Settings;

/**
 * Dữ liệu cho Admin → Plugin (plugin-system §5b): danh sách và trang chi tiết. Chỉ đọc; thao tác vòng đời đi qua
 * PluginManager để giữ nguyên mọi kiểm tra (phụ thuộc, extension point bắt buộc, việc dở dang, dữ liệu plugin).
 */
final class PluginOverview
{
    public function __construct(
        private readonly ManifestRepository $manifests,
        private readonly DependencyResolver $resolver,
        private readonly PluginLoader $loader,
        private readonly PluginHealth $health,
        private readonly PluginManager $plugins,
        private readonly Settings $settings,
        private readonly HookManager $hooks,
        private readonly AdminNavigation $navigation,
        private readonly string $coreVersion,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function rows(string $locale): array
    {
        $records = $this->records();
        $failures = $this->loader->failures();
        $health = $this->health->last();
        $withSettings = $this->namespacesWithSettings();

        return array_values(array_map(function (PluginManifest $manifest) use ($records, $failures, $health, $withSettings, $locale): array {
            $record = $records->get($manifest->id);

            return [
                'id' => $manifest->id,
                'name' => $manifest->displayName($locale),
                'description' => $manifest->displayDescription($locale),
                'author' => $manifest->author,
                'version' => $manifest->version,
                'installed_version' => $record?->version,
                'kind' => $manifest->kind,
                'bundled' => $manifest->bundled,
                'status' => $this->status($record),
                'error' => $failures[$manifest->id] ?? $record?->last_error,
                'health' => $health[$manifest->id] ?? null,
                'has_icon' => $manifest->iconPath() !== null,
                'upgrade_available' => $this->upgradeAvailable($manifest, $record),
                'has_settings' => isset($withSettings[$manifest->id]),
            ];
        }, $this->manifests->all()));
    }

    /**
     * @return array<string, mixed>|null
     */
    public function detail(string $pluginId, string $locale): ?array
    {
        $manifest = $this->manifests->find($pluginId);
        if ($manifest === null) {
            return null;
        }

        $records = $this->records();
        $record = $records->get($pluginId);
        $status = $this->status($record);
        $installed = $records->keys()->map(fn ($id): string => (string) $id)->all();
        $active = in_array($status, [PluginStatus::Enabled->value, PluginStatus::Draining->value], true);
        $row = array_values(array_filter($this->rows($locale), fn (array $row): bool => $row['id'] === $pluginId))[0];

        return [
            ...$row,
            'installed_at' => $record?->installed_at?->toIso8601String(),
            'path' => str_replace(base_path().'/', '', $manifest->path),
            'requires_core' => $manifest->requiresCore,
            'requires_plugins' => array_map(fn (string $id, string $constraint): array => [
                'id' => $id,
                'constraint' => $constraint,
                'name' => $this->manifests->find($id)?->displayName($locale),
                'status' => $this->status($records->get($id)),
            ], array_keys($manifest->requiresPlugins), array_values($manifest->requiresPlugins)),
            'dependents' => $this->resolver->dependentsOf($pluginId, $this->manifests->all(), $installed),
            'conflicts' => $manifest->conflicts,
            'capabilities' => $manifest->requiresCapabilities,
            'permissions' => $manifest->permissions,
            'data' => ['owned' => $manifest->data->owned, 'references' => $manifest->data->references, 'retained' => $manifest->data->retained],
            // Chưa cài: lý do không cài được (Core không tương thích, thiếu plugin phụ thuộc, xung đột).
            'problems' => $record === null ? array_map(fn (DependencyProblem $problem): string => $problem->message, $this->resolver->problemsFor($pluginId, $this->manifests->all(), $installed, $this->coreVersion)) : [],
            'in_use' => $active ? $this->plugins->inUse($pluginId) : [],
            'hooks' => array_keys(array_filter($this->hooks->listenerOwners(), fn (array $owners): bool => in_array($pluginId, $owners, true))),
            'admin_pages' => $active ? $this->navigation->forPlugin($pluginId) : [],
            'actions' => $this->actions($manifest, $record),
        ];
    }

    /**
     * Thao tác hợp lệ theo trạng thái (giao diện chỉ hiện nút này; PluginManager vẫn kiểm tra lại).
     *
     * @return list<string>
     */
    private function actions(PluginManifest $manifest, ?PluginRecord $record): array
    {
        if ($record === null) {
            return ['install'];
        }

        $actions = match ($record->status) {
            PluginStatus::Installed, PluginStatus::Disabled => ['enable', 'uninstall'],
            PluginStatus::Enabled => ['disable'],
            PluginStatus::Draining => ['enable', 'disable'],
            PluginStatus::Failed => ['uninstall'],
        };

        return $this->upgradeAvailable($manifest, $record) ? [...$actions, 'upgrade'] : $actions;
    }

    private function upgradeAvailable(PluginManifest $manifest, ?PluginRecord $record): bool
    {
        return $record !== null && Comparator::greaterThan($manifest->version, $record->version);
    }

    private function status(?PluginRecord $record): string
    {
        return $record?->status->value ?? 'discovered';
    }

    /**
     * @return Collection<string, PluginRecord>
     */
    private function records(): Collection
    {
        return PluginRecord::query()->get()->keyBy('id');
    }

    /**
     * @return array<string, true>
     */
    private function namespacesWithSettings(): array
    {
        $namespaces = [];
        foreach ($this->settings->definitions() as $definition) {
            $namespaces[$definition->namespace] = true;
        }

        return $namespaces;
    }
}
