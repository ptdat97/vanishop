<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Storefront;

use Closure;
use Modules\Extension\Application\Plugins\PluginActivation;

/**
 * Mục do plugin thêm vào menu tài khoản khách của native storefront (`accountPage()`, ADR-030 §4.B).
 */
final class AccountPages
{
    /** @var array<string, array{plugin: string, key: string, label: string, route: string, order: int}> */
    private array $pages = [];

    /**
     * @param  Closure(): PluginActivation  $activation
     */
    public function __construct(private readonly Closure $activation) {}

    public function add(string $plugin, string $key, string $label, string $route, int $order = 500): void
    {
        $this->pages["{$plugin}:{$key}"] = compact('plugin', 'key', 'label', 'route', 'order');
    }

    /**
     * @return list<array{key: string, label: string, url: string}>
     */
    public function active(): array
    {
        $pages = array_filter($this->pages, fn (array $page): bool => ($this->activation)()->isActive($page['plugin']) && app('router')->has($page['route']));
        usort($pages, fn (array $a, array $b): int => [$a['order'], $a['label']] <=> [$b['order'], $b['label']]);

        return array_map(fn (array $page): array => ['key' => "{$page['plugin']}:{$page['key']}", 'label' => $page['label'], 'url' => route($page['route'])], $pages);
    }
}
