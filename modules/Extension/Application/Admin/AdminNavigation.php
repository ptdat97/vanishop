<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Admin;

use Closure;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Modules\Extension\Application\Plugins\PluginActivation;

/**
 * Registry menu Admin (singleton, đăng ký lúc boot). Mục hiển thị được tính theo từng request:
 * quyền của nhân viên và trạng thái bật của plugin trong phạm vi hiện tại.
 *
 * Mục có thể thuộc một nhóm (accordion trên sidebar, Core 0.3.38). Mục không nhóm hiện ở cấp trên cùng. Mục khai
 * báo nhóm chưa đăng ký rơi vào nhóm `extensions` ("Mở rộng").
 */
final class AdminNavigation
{
    public const FALLBACK_GROUP = 'extensions';

    /** @var array<string, array{key: string, label: string, route: string, permission: string|null, order: int, plugin: string|null, group: string|null, when: (Closure(): bool)|null}> */
    private array $items = [];

    /** @var array<string, array{key: string, label: string, order: int}> */
    private array $groups = [self::FALLBACK_GROUP => ['key' => self::FALLBACK_GROUP, 'label' => 'Mở rộng', 'order' => 850]];

    /**
     * @param  Closure(): PluginActivation  $activation
     */
    public function __construct(private readonly Closure $activation) {}

    /**
     * @param  (Closure(): bool)|null  $when  điều kiện hiển thị thêm (vd. chỉ hiện "Báo cáo" khi có báo cáo xem được)
     */
    public function add(string $key, string $label, string $route, ?string $permission = null, int $order = 100, ?string $pluginId = null, ?Closure $when = null, ?string $group = null): void
    {
        $this->items[$key] = compact('key', 'label', 'route', 'permission', 'order', 'when', 'group') + ['plugin' => $pluginId];
    }

    /**
     * Nhóm menu (accordion). `order` xếp nhóm xen kẽ với mục không nhóm cùng thang thứ tự.
     */
    public function group(string $key, string $label, int $order): void
    {
        $this->groups[$key] = compact('key', 'label', 'order');
    }

    /**
     * Các nhóm có ít nhất một mục hiển thị, theo thứ tự.
     *
     * @return list<array{key: string, label: string, order: int}>
     */
    public function visibleGroups(): array
    {
        $used = array_unique(array_filter(array_column($this->visibleItems(), 'group')));
        $groups = array_values(array_filter($this->groups, fn (array $group): bool => in_array($group['key'], $used, true)));
        usort($groups, fn (array $a, array $b): int => [$a['order'], $a['key']] <=> [$b['order'], $b['key']]);

        return array_map(fn (array $group): array => [...$group, 'label' => __($group['label'])], $groups);
    }

    /**
     * @return list<array{key: string, label: string, url: string, group: string|null}>
     */
    public function visibleItems(): array
    {
        $items = array_filter($this->items, fn (array $item): bool => Route::has($item['route'])
            && ($item['permission'] === null || Gate::allows($item['permission']))
            && ($item['plugin'] === null || ($this->activation)()->isActive($item['plugin']))
            && ($item['when'] === null || ($item['when'])()));

        usort($items, fn (array $a, array $b): int => [$a['order'], $a['key']] <=> [$b['order'], $b['key']]);

        return array_map(fn (array $item): array => [
            'key' => $item['key'],
            'label' => __($item['label']),
            'url' => route($item['route']),
            'group' => $item['group'] === null ? null : (isset($this->groups[$item['group']]) ? $item['group'] : self::FALLBACK_GROUP),
        ], $items);
    }
}
