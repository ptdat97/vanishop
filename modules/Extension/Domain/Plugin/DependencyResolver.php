<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Plugin;

use Composer\Semver\Semver;
use UnexpectedValueException;

/**
 * Kiểm tra tương thích Core, phụ thuộc, xung đột và tính thứ tự nạp (topological sort).
 */
final class DependencyResolver
{
    /**
     * Các vấn đề khiến $pluginId không thể cài/bật khi $active là tập plugin sẽ cùng hoạt động.
     *
     * @param  array<string, PluginManifest>  $available  mọi plugin tìm thấy
     * @param  list<string>  $active  plugin đang/ sẽ hoạt động (không gồm $pluginId)
     * @return list<DependencyProblem>
     */
    public function problemsFor(string $pluginId, array $available, array $active, string $coreVersion): array
    {
        $manifest = $available[$pluginId] ?? null;
        if ($manifest === null) {
            return [new DependencyProblem($pluginId, 'not_found', "Không tìm thấy plugin [{$pluginId}].")];
        }

        $problems = [];

        if (! $this->satisfies($coreVersion, $manifest->requiresCore)) {
            $problems[] = new DependencyProblem($pluginId, 'incompatible_core',
                "Cần VaniShop {$manifest->requiresCore}, hiện tại là {$coreVersion}.");
        }

        foreach ($manifest->requiresPlugins as $dependencyId => $constraint) {
            $dependency = $available[$dependencyId] ?? null;

            if ($dependency === null) {
                $problems[] = new DependencyProblem($pluginId, 'missing_dependency', "Thiếu plugin phụ thuộc [{$dependencyId}].");
            } elseif (! $this->satisfies($dependency->version, $constraint)) {
                $problems[] = new DependencyProblem($pluginId, 'incompatible_dependency',
                    "Cần [{$dependencyId}] {$constraint}, hiện có {$dependency->version}.");
            } elseif (! in_array($dependencyId, $active, true)) {
                $problems[] = new DependencyProblem($pluginId, 'inactive_dependency', "Plugin phụ thuộc [{$dependencyId}] chưa được cài/bật.");
            }
        }

        foreach ($active as $otherId) {
            $other = $available[$otherId] ?? null;
            $conflicts = in_array($otherId, $manifest->conflicts, true)
                || ($other !== null && in_array($pluginId, $other->conflicts, true));

            if ($conflicts) {
                $problems[] = new DependencyProblem($pluginId, 'conflict', "Xung đột với plugin [{$otherId}].");
            }
        }

        return $problems;
    }

    /**
     * Plugin đang hoạt động phụ thuộc vào $pluginId (chặn gỡ/tắt).
     *
     * @param  array<string, PluginManifest>  $available
     * @param  list<string>  $active
     * @return list<string>
     */
    public function dependentsOf(string $pluginId, array $available, array $active): array
    {
        return array_values(array_filter(
            $active,
            fn (string $id): bool => isset($available[$id]->requiresPlugins[$pluginId]),
        ));
    }

    /**
     * Thứ tự nạp: phụ thuộc trước. Ném CircularDependency nếu có vòng.
     *
     * @param  array<string, PluginManifest>  $manifests
     * @return list<string>
     */
    public function loadOrder(array $manifests): array
    {
        $ordered = [];
        $state = [];

        $visit = function (string $id, array $path) use (&$visit, &$ordered, &$state, $manifests): void {
            if (($state[$id] ?? null) === 'done') {
                return;
            }
            if (($state[$id] ?? null) === 'visiting') {
                throw new CircularDependency([...$path, $id]);
            }

            $state[$id] = 'visiting';
            foreach (array_keys($manifests[$id]->requiresPlugins) as $dependencyId) {
                if (isset($manifests[$dependencyId])) {
                    $visit($dependencyId, [...$path, $id]);
                }
            }
            $state[$id] = 'done';
            $ordered[] = $id;
        };

        $ids = array_keys($manifests);
        sort($ids);
        foreach ($ids as $id) {
            $visit($id, []);
        }

        return $ordered;
    }

    private function satisfies(string $version, string $constraint): bool
    {
        try {
            return Semver::satisfies($version, $constraint);
        } catch (UnexpectedValueException) {
            return false;
        }
    }
}
