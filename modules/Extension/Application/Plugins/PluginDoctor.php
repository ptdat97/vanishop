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
                $add($id, self::ERROR, 'manifest_missing', 'Đã cài nhưng không còn thư mục/manifest trong custom/plugin.');

                continue;
            }

            if ($record->status === PluginStatus::Draining) {
                $add($id, self::WARNING, 'draining', 'Đang ngừng: không nhận giao dịch mới, tự tắt khi hết việc dở dang (vani:plugin:finish-draining).');
            }

            if ($record->status === PluginStatus::Failed) {
                $add($id, self::ERROR, 'failed', 'Plugin ở trạng thái failed: '.($record->last_error ?? 'không rõ lỗi'));
            }

            if (Comparator::greaterThan($manifest->version, $record->version)) {
                $add($id, self::WARNING, 'upgrade_pending', "Code {$manifest->version}, đã cài {$record->version} — chạy vani:plugin:upgrade {$id}.");
            } elseif (Comparator::lessThan($manifest->version, $record->version)) {
                $add($id, self::ERROR, 'downgraded', "Code {$manifest->version} cũ hơn bản đã cài {$record->version}.");
            }

            if (! class_exists($manifest->provider)) {
                $add($id, self::ERROR, 'provider_missing', "Không nạp được provider {$manifest->provider} (autoload?).");
            }

            $active = array_values(array_diff($enabled, [$id]));
            if ($running($record)) {
                foreach ($this->capabilities->missingFor($manifest, $active) as $tag) {
                    $add($id, self::ERROR, 'capability_missing', 'Thiếu capability '.$this->capabilities->describe($tag).': không có implementation nào đang bật.');
                }
            }
            foreach ($this->resolver->problemsFor($id, $manifests, $running($record) ? $active : array_values(array_diff($records->keys()->all(), [$id])), $this->coreVersion) as $problem) {
                $add($id, $problem->code === 'inactive_dependency' && ! $running($record) ? self::WARNING : self::ERROR, $problem->code, $problem->message);
            }

            $migrations = is_dir($manifest->path.'/Database/migrations') ? glob($manifest->path.'/Database/migrations/*.php') ?: [] : [];
            $pending = array_diff(array_map(fn (string $file): string => basename($file, '.php'), $migrations), $ranMigrations);
            if ($pending !== []) {
                $add($id, self::WARNING, 'migrations_pending', count($pending).' migration chưa chạy: '.implode(', ', $pending));
            }

            // Khai báo dữ liệu (0.3.24): plugin có migration phải khai data.owned; bảng/cột khai báo phải tồn tại.
            if ($migrations !== [] && $manifest->data->owned === []) {
                $add($id, self::WARNING, 'data_undeclared', 'Có migration nhưng manifest chưa khai báo data.owned — uninstall --purge không kiểm tra được tham chiếu.');
            }
            if ($pending === []) {
                foreach ($manifest->data->owned as $table) {
                    if (! Schema::hasTable($table)) {
                        $add($id, self::ERROR, 'data_owned_missing', "Bảng khai báo trong data.owned không tồn tại: {$table}.");
                    }
                }
                foreach ($manifest->data->references as $from => $to) {
                    [$table, $column] = explode('.', $to);
                    if (! Schema::hasColumn($table, $column)) {
                        $add($id, self::WARNING, 'data_reference_invalid', "data.references {$from} → {$to}: cột đích không tồn tại.");
                    }
                }
            }
        }

        foreach ($manifests as $id => $manifest) {
            // Plugin hệ thống mới ra ở bản Core sau chưa được cài trên hệ thống đang chạy (deploy chỉ migrate).
            if (! $records->has($id) && $manifest->bundled) {
                $add($id, self::WARNING, 'bundled_not_installed', 'Plugin hệ thống chưa cài — chạy php artisan vani:install.');
            }
            if (! $records->has($id) && ! $this->satisfiesCore($manifest->requiresCore)) {
                $add($id, self::WARNING, 'incompatible_core', "Chưa cài; cần VaniShop {$manifest->requiresCore}, hiện tại {$this->coreVersion}.");
            }
        }

        // Plugin khai loại gắn với extension point mà không đóng góp implementation (chỉ xét provider đã nạp ở tiến trình này).
        $kindContracts = $this->extensions->kindContracts();
        foreach ($this->loader->loaded() as $id) {
            $tag = isset($manifests[$id]) ? ($kindContracts[$manifests[$id]->kind] ?? null) : null;
            if ($tag !== null && ! in_array($id, $this->extensions->providers($tag), true)) {
                $add($id, self::WARNING, 'kind_mismatch', "Loại [{$manifests[$id]->kind}] nhưng không đóng góp implementation cho [{$tag}].");
            }
        }

        foreach ($this->health->run() as $id => $result) {
            if ($result['status'] !== 'ok') {
                $add($id, $result['status'] === 'error' ? self::ERROR : self::WARNING, 'health_'.$result['status'], $result['message']);
            }
        }

        foreach ($this->required->missing($enabled) as $tag => $label) {
            $add('core', self::ERROR, 'required_extension_missing', "Extension point bắt buộc [{$tag}] ({$label}) không có implementation nào đang bật.");
        }

        foreach ($this->extensions->doctorChecks() as $code => $check) {
            try {
                $message = $check();
            } catch (Throwable $exception) {
                $add('core', self::ERROR, 'doctor_check_failed', "Kiểm tra [{$code}] lỗi: {$exception->getMessage()}");

                continue;
            }
            if ($message !== null) {
                $add('core', self::WARNING, $code, $message);
            }
        }

        foreach ($this->loader->failures() as $id => $error) {
            $add($id, self::ERROR, 'boot_failed', "Lỗi khi nạp ở tiến trình này: {$error}");
        }

        $store = (string) config('cache.default');
        if (app()->isProduction() && in_array((string) config("cache.stores.{$store}.driver"), ['file', 'array'], true)) {
            $add('core', self::WARNING, 'cache_not_shared', "Cache store [{$store}] không dùng chung giữa server — trạng thái bật plugin có thể lệch giữa các máy.");
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
