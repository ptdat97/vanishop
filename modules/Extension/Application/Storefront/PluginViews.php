<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Storefront;

/**
 * View storefront của plugin (namespace → slug + thư mục gốc). Theme đang hoạt động override được view plugin tại
 * `custom/theme/<theme>/plugins/<slug>/` (ADR-025: thay khối bằng override view, không viết lại HTML).
 */
final class PluginViews
{
    /** @var array<string, array{slug: string, path: string}> */
    private array $namespaces = [];

    public function add(string $namespace, string $slug, string $path): void
    {
        $this->namespaces[$namespace] = ['slug' => $slug, 'path' => $path];
    }

    /**
     * @return array<string, array{slug: string, path: string}>
     */
    public function all(): array
    {
        return $this->namespaces;
    }
}
