<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers\Admin;

use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginHealth;
use Modules\Extension\Application\Plugins\PluginLoader;
use Modules\Extension\Domain\Plugin\PluginManifest;
use Modules\Extension\Persistence\Models\PluginRecord;

final class PluginController
{
    public function index(ManifestRepository $manifests, PluginLoader $loader, PluginHealth $health): Response
    {
        Gate::authorize('extension.plugins.view');

        $records = PluginRecord::query()->get()->keyBy('id');
        $failures = $loader->failures();
        $healthResults = $health->last();

        $plugins = array_map(function (PluginManifest $manifest) use ($records, $failures, $healthResults): array {
            $record = $records->get($manifest->id);

            return [
                'id' => $manifest->id,
                'name' => $manifest->displayName(app()->getLocale()),
                'version' => $manifest->version,
                'kind' => $manifest->kind,
                'bundled' => $manifest->bundled,
                'status' => $record?->status->value ?? 'discovered',
                'error' => $failures[$manifest->id] ?? $record?->last_error,
                'health' => $healthResults[$manifest->id] ?? null,
            ];
        }, array_values($manifests->all()));

        return Inertia::render('Extension::Plugins/Index', [
            'plugins' => $plugins,
            'invalid' => array_values($manifests->invalid()),
        ]);
    }
}
