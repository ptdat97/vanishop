<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Domain\Plugin\PluginManifest;

/**
 * Capability giữa plugin (roadmap Phase 5, 0.3.33): plugin khai `requires.capabilities` = tag extension point cần ít
 * nhất một implementation đang có hiệu lực (Core, hoặc plugin đang bật). Chặn bật plugin khi chưa có; chặn tắt/gỡ plugin
 * là nguồn cuối cùng của capability mà plugin đang chạy khác cần; doctor báo khi thiếu.
 *
 * Nguồn implementation lấy từ `Extensions::providers()` (Core + plugin có provider đã nạp ở tiến trình này).
 */
final class PluginCapabilities
{
    public function __construct(private readonly Extensions $extensions) {}

    /**
     * Capability của $manifest chưa có nguồn nào trong $activeIds (cộng chính nó).
     *
     * @param  list<string>  $activeIds
     * @return list<string> tag còn thiếu
     */
    public function missingFor(PluginManifest $manifest, array $activeIds): array
    {
        $active = [...$activeIds, $manifest->id];

        return array_values(array_filter($manifest->requiresCapabilities, fn (string $tag): bool => ! $this->provided($tag, $active)));
    }

    /**
     * Capability sẽ mất nếu $pluginId ngừng: plugin đang chạy => tag chỉ còn $pluginId cung cấp.
     *
     * @param  array<string, PluginManifest>  $manifests
     * @param  list<string>  $runningIds  plugin đang chạy (cần capability)
     * @param  list<string>  $providingIds  plugin được tính là nguồn thay thế (đang bật, không tính draining)
     * @return list<string> mô tả "plugin cần tag"
     */
    public function brokenWithout(string $pluginId, array $manifests, array $runningIds, array $providingIds): array
    {
        if (! in_array($pluginId, $this->allProviders($manifests), true)) {
            return [];
        }

        $remaining = array_values(array_diff($providingIds, [$pluginId]));
        $broken = [];
        foreach (array_diff($runningIds, [$pluginId]) as $dependentId) {
            foreach ($manifests[$dependentId]->requiresCapabilities ?? [] as $tag) {
                if (in_array($pluginId, $this->extensions->providers($tag), true) && ! $this->provided($tag, [...$remaining, $dependentId])) {
                    $broken[] = "{$dependentId} cần [{$tag}]";
                }
            }
        }

        return $broken;
    }

    /**
     * Nhãn dễ đọc cho tag (loại plugin gắn với tag nếu có), dùng trong thông báo.
     */
    public function describe(string $tag): string
    {
        $kind = array_search($tag, $this->extensions->kindContracts(), true);

        return $kind === false ? $tag : "{$tag} (loại {$kind})";
    }

    /**
     * @param  list<string>  $activeIds
     */
    private function provided(string $tag, array $activeIds): bool
    {
        foreach ($this->extensions->providers($tag) as $provider) {
            if ($provider === null || in_array($provider, $activeIds, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Plugin là nguồn của ít nhất một capability được ai đó khai báo.
     *
     * @param  array<string, PluginManifest>  $manifests
     * @return list<string>
     */
    private function allProviders(array $manifests): array
    {
        $tags = array_unique(array_merge(...array_values(array_map(fn (PluginManifest $manifest): array => $manifest->requiresCapabilities, $manifests ?: []) ?: [[]])));
        $providers = [];
        foreach ($tags as $tag) {
            array_push($providers, ...array_filter($this->extensions->providers($tag)));
        }

        return array_values(array_unique($providers));
    }
}
