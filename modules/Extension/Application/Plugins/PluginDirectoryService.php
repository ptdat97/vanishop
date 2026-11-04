<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Illuminate\Support\Facades\App;
use Modules\Extension\Contracts\Data\PluginInfo;
use Modules\Extension\Contracts\PluginDirectory;

final class PluginDirectoryService implements PluginDirectory
{
    public function __construct(
        private readonly ManifestRepository $manifests,
        private readonly PluginActivation $activation,
        private readonly PluginAssets $assets,
    ) {}

    public function find(string $pluginId): ?PluginInfo
    {
        $manifest = $this->manifests->find($pluginId);
        if ($manifest === null) {
            return null;
        }

        return new PluginInfo(
            id: $manifest->id,
            name: $manifest->name[App::getLocale()] ?? $manifest->name['*'] ?? (string) (array_values($manifest->name)[0] ?? $manifest->id),
            version: $manifest->version,
            kind: $manifest->kind,
            enabled: $this->activation->isActive($pluginId),
            author: $manifest->author,
        );
    }

    public function asset(string $pluginId, string $path): ?string
    {
        return $this->assets->url($pluginId, $path);
    }
}
