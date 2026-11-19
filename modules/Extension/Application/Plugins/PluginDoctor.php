<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Domain\Plugin\DependencyResolver;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;
use Throwable;

/**
 * Chẩn đoán plugin (vani:plugin:doctor): tương thích Core/phụ thuộc/xung đột, version lệch, migration chưa chạy,
 * provider không tồn tại, plugin failed, extension point bắt buộc thiếu implementation (ADR-029), cấu hình hạ tầng.
 */
final class PluginDoctor
{
    public const ERROR = 'error';

    public const WARNING = 'warning';

    public function __construct(
        private readonly ManifestRepository $manifests,
        private readonly DependencyResolver $resolver,
        private readonly PluginLoader $loader,
        private readonly RequiredExtensions $required,
        private readonly Extensions $extensions,
        private readonly PluginHealth $health,
        private readonly string $coreVersion,
        private readonly PluginCapabilities $capabilities,
        private readonly PluginSeeders $seeders,
        private readonly DeprecationScanner $deprecations,
        private readonly PluginBreaker $breaker,
    ) {}

    /**
     * @return list<array{plugin: string, level: string, code: string, message: string}>
     */
    public function diagnose(): array
    {
        $issues = [];
        $add = function (string $plugin, string $level, string $code, string $message) use (&$issues): void {
            $issues[] = ['plugin' => $plugin, 'level' => $level, 'code' => $code, 'message' => $message];
        };

        $manifests = $this->manifests->all();
        $records = PluginRecord::query()->get()->keyBy('id');
        // Đang chạy = bật hoặc đang ngừng (draining vẫn nạp provider, vẫn xử lý giao dịch dở).
        $running = fn (PluginRecord $record): bool => in_array($record->status, [PluginStatus::Enabled, PluginStatus::Draining], true);
        $enabled = $records->filter($running)->keys()->all();
        $ranMigrations = DB::table('migrations')->pluck('migration')->all();

        foreach ($records as $id => $record) {
            $manifest = $manifests[$id] ?? null;
            if ($manifest === null) {
                $add($id, self::ERROR, 'manifest_missing', __('extension::texts.plugin_doctor.installed_but_directory_manifest_no'));

                continue;
            }

            if ($record->status === PluginStatus::Draining) {
                $add($id, self::WARNING, 'draining', __('extension::texts.plugin_doctor.stopping_no_new_transactions_disables'));
            }

            if ($record->status === PluginStatus::Failed) {
                $add($id, self::ERROR, 'failed', __('extension::texts.plugin_doctor.plugin_failed_state').($record->last_error ?? __('extension::texts.plugin_doctor.unknown_error')));
            }

            if (Comparator::greaterThan($manifest->version, $record->version)) {
                $add($id, self::WARNING, 'upgrade_pending', __('extension::texts.plugin_doctor.code_version_installed_version2_run', ['version' => $manifest->version, 'version2' => $record->version, 'id' => $id]));
            } elseif (Comparator::lessThan($manifest->version, $record->version)) {
                $add($id, self::ERROR, 'downgraded', __('extension::texts.plugin_doctor.code_version_older_than_installed', ['version' => $manifest->version, 'version2' => $record->version]));
            }

            if (! class_exists($manifest->provider)) {
                $add($id, self::ERROR, 'provider_missing', __('extension::texts.plugin_doctor.could_not_load_provider_provider', ['provider' => $manifest->provider]));
            }

            $active = array_values(array_diff($enabled, [$id]));
            if ($running($record)) {
                foreach ($this->capabilities->missingFor($manifest, $active) as $tag) {
                    $add($id, self::ERROR, 'capability_missing', __('extension::texts.plugin_doctor.missing_capability').$this->capabilities->describe($tag).__('extension::texts.plugin_doctor.no_enabled_implementation'));
                }
            }
            foreach ($this->resolver->problemsFor($id, $manifests, $running($record) ? $active : array_values(array_diff($records->keys()->all(), [$id])), $this->coreVersion) as $problem) {
                $add($id, $problem->code === 'inactive_dependency' && ! $running($record) ? self::WARNING : self::ERROR, $problem->code, $problem->message);
            }

            $migrations = is_dir($manifest->path.'/Database/migrations') ? glob($manifest->path.'/Database/migrations/*.php') ?: [] : [];
            $pending = array_diff(array_map(fn (string $file): string => basename($file, '.php'), $migrations), $ranMigrations);
            if ($pending !== []) {
                $add($id, self::WARNING, 'migrations_pending', count($pending).__('extension::texts.plugin_doctor.migrations_not_run').implode(', ', $pending));
            }

            $pendingSeeders = $pending === [] ? $this->seeders->pending($manifest) : [];
            if ($pendingSeeders !== []) {
                $add($id, self::WARNING, 'seeders_pending', count($pendingSeeders).__('extension::texts.plugin_doctor.seeders_not_run_run_vanipluginseed').$id.': '.implode(', ', $pendingSeeders));
            }

            // Khai báo dữ liệu (0.3.24): plugin có migration phải khai data.owned; bảng/cột khai báo phải tồn tại.
            if ($migrations !== [] && $manifest->data->owned === []) {
                $add($id, self::WARNING, 'data_undeclared', __('extension::texts.plugin_doctor.has_migrations_but_manifest_does'));
            }
            if ($pending === []) {
                foreach ($manifest->data->owned as $table) {
                    if (! Schema::hasTable($table)) {
                        $add($id, self::ERROR, 'data_owned_missing', __('extension::texts.plugin_doctor.table_declared_data_owned_does', ['table' => $table]));
                    }
                }
                foreach ($manifest->data->references as $from => $to) {
                    [$table, $column] = explode('.', $to);
                    if (! Schema::hasColumn($table, $column)) {
                        $add($id, self::WARNING, 'data_reference_invalid', __('extension::texts.plugin_doctor.data_references_from_target_column', ['from' => $from, 'to' => $to]));
                    }
                }
            }
        }

        foreach ($manifests as $id => $manifest) {
            // Plugin hệ thống mới ra ở bản Core sau chưa được cài trên hệ thống đang chạy (deploy chỉ migrate).
            if (! $records->has($id) && $manifest->bundled) {
                $add($id, self::WARNING, 'bundled_not_installed', __('extension::texts.plugin_doctor.system_plugin_not_installed_run'));
            }
            if (! $records->has($id) && $manifest->requiresCore !== null && ! $this->satisfiesCore($manifest->requiresCore)) {
                $add($id, self::WARNING, 'incompatible_core', __('extension::texts.plugin_doctor.not_installed_requires_vanishop_requires', ['requires_core' => $manifest->requiresCore, 'core_version' => $this->coreVersion]));
            }
            // ADR-048: plugin chưa cài viết cho version API cho plugin khác (vd. bản mới cần extension_api 2.x).
            if (! $records->has($id)) {
                foreach ($this->resolver->problemsFor($id, $manifests, [], $this->coreVersion) as $problem) {
                    if ($problem->code === 'incompatible_extension_api') {
                        $add($id, self::WARNING, 'incompatible_extension_api', __('extension::texts.plugin_doctor.not_installed_message', ['message' => $problem->message]));
                    }
                }
            }
        }

        // Circuit breaker đang mở (K3/P6): plugin lỗi liên tục ở luồng đọc/hiển thị, đang bị bỏ qua.
        foreach ($records as $id => $record) {
            if ($running($record) && $this->breaker->isOpen($id)) {
                $add($id, self::WARNING, 'breaker_open', __('extension::texts.plugin_doctor.plugin_keeps_failing_read_display', ['cooldown_seconds' => $this->breaker->cooldownSeconds()]));
            }
        }

        // API đã ngừng khuyến nghị mà plugin đã cài còn dùng (K3/P5) — quét tĩnh, không nạp plugin.
        foreach ($records as $id => $record) {
            foreach (isset($manifests[$id]) ? $this->deprecations->scan($manifests[$id]->path) : [] as $usage) {
                $add($id, self::WARNING, 'deprecated_usage', __('extension::texts.plugin_doctor.uses_deprecated_feature_message', ['feature' => $usage['feature'], 'message' => $usage['message'], 'at' => $usage['at']]));
            }
        }

        // Plugin khai loại gắn với extension point mà không đóng góp implementation (chỉ xét provider đã nạp ở tiến trình này).
        $kindContracts = $this->extensions->kindContracts();
        foreach ($this->loader->loaded() as $id) {
            $tag = isset($manifests[$id]) ? ($kindContracts[$manifests[$id]->kind] ?? null) : null;
            if ($tag !== null && ! in_array($id, $this->extensions->providers($tag), true)) {
                $add($id, self::WARNING, 'kind_mismatch', __('extension::texts.plugin_doctor.type_kind_but_contributes_no', ['kind' => $manifests[$id]->kind, 'tag' => $tag]));
            }
        }

        foreach ($this->health->run() as $id => $result) {
            if ($result['status'] !== 'ok') {
                $add($id, $result['status'] === 'error' ? self::ERROR : self::WARNING, 'health_'.$result['status'], $result['message']);
            }
        }

        foreach ($this->required->missing($enabled) as $tag => $label) {
            $add('core', self::ERROR, 'required_extension_missing', __('extension::texts.plugin_doctor.required_extension_point_tag_label', ['tag' => $tag, 'label' => __($label)]));
        }

        foreach ($this->extensions->doctorChecks() as $code => $check) {
            try {
                $message = $check();
            } catch (Throwable $exception) {
                $add('core', self::ERROR, 'doctor_check_failed', __('extension::texts.plugin_doctor.check_code_failed_get_message', ['code' => $code, 'get_message' => $exception->getMessage()]));

                continue;
            }
            if ($message !== null) {
                $add('core', self::WARNING, $code, $message);
            }
        }

        foreach ($this->loader->failures() as $id => $error) {
            $add($id, self::ERROR, 'boot_failed', __('extension::texts.plugin_doctor.error_while_loading_process_error', ['error' => $error]));
        }

        $store = (string) config('cache.default');
        if (app()->isProduction() && in_array((string) config("cache.stores.{$store}.driver"), ['file', 'array'], true)) {
            $add('core', self::WARNING, 'cache_not_shared', __('extension::texts.plugin_doctor.cache_store_store_not_shared', ['store' => $store]));
        }

        return $issues;
    }

    private function satisfiesCore(string $constraint): bool
    {
        try {
            return Semver::satisfies($this->coreVersion, $constraint);
        } catch (Throwable) {
            return false;
        }
    }
}
