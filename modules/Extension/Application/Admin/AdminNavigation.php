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
 */
final class AdminNavigation
{
    /** @var array<string, array{key: string, label: string, route: string, permission: string|null, order: int, plugin: string|null, when: (Closure(): bool)|null}> */
    private array $items = [];

    /**
     * @param  Closure(): PluginActivation  $activation
     */
    public function __construct(private readonly Closure $activation) {}

    /**
     * @param  (Closure(): bool)|null  $when  điều kiện hiển thị thêm (vd. chỉ hiện "Báo cáo" khi có báo cáo xem được)
     */
    public function add(string $key, string $label, string $route, ?string $permission = null, int $order = 100, ?string $pluginId = null, ?Closure $when = null): void
    {
        $this->items[$key] = compact('key', 'label', 'route', 'permission', 'order', 'when') + ['plugin' => $pluginId];
    }

    /**
     * @return list<array{key: string, label: string, url: string}>
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
        ], $items);
    }
}
