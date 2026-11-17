<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts;

/**
 * Service contract: namespace view storefront của plugin (theme override được) — C1.
 */
interface PluginViews
{
    public function add(string $namespace, string $slug, string $path): void;

    /**
     * @return array<string, array{slug: string, path: string}>
     */
    public function all(): array;
}
