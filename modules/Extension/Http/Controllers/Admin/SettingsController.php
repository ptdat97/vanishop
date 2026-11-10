<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Settings\SettingsAdmin;
use Modules\Identity\Contracts\Data\ScopeRef;

/**
 * Cấu hình của cửa hàng cho Core (`core`) và plugin (namespace = plugin id).
 */
final class SettingsController
{
    public function index(SettingsAdmin $admin, ManifestRepository $manifests): Response
    {
        Gate::authorize('settings.manage', [ScopeRef::owner()]);
        $cards = array_map(function (string $namespace) use ($manifests): array {
            $manifest = $manifests->find($namespace);

            return [
                'namespace' => $namespace,
                'title' => $namespace === 'core' ? 'Cấu hình chung' : ($manifest?->displayName(app()->getLocale()) ?? "Plugin {$namespace}"),
                'subtitle' => $namespace === 'core' ? 'Thiết lập nền tảng' : $namespace,
                'url' => route('admin.settings.show', $namespace),
            ];
        }, $admin->namespaces());

        return Inertia::render('Extension::Settings/Index', [
            'cards' => $cards,
        ]);
    }

    public function show(string $namespace, SettingsAdmin $admin, ManifestRepository $manifests): Response
    {
        Gate::authorize('settings.manage', [ScopeRef::owner()]);
        abort_unless(in_array($namespace, $admin->namespaces(), true), 404);
        $manifest = $manifests->find($namespace);

        return Inertia::render('Extension::Settings/Show', [
            'baseUrl' => route('admin.settings.index'),
            'action' => route('admin.settings.update'),
            'namespace' => $namespace,
            'title' => $namespace === 'core' ? 'Cấu hình chung' : ($manifest?->displayName(app()->getLocale()) ?? "Plugin {$namespace}"),
            'subtitle' => $namespace === 'core' ? 'Thiết lập nền tảng' : $namespace,
            'fields' => $admin->form($namespace),
        ]);
    }

    public function update(Request $request, SettingsAdmin $admin): RedirectResponse
    {
        Gate::authorize('settings.manage', [ScopeRef::owner()]);
        $data = $request->validate([
            'namespace' => ['required', 'string', 'max:64'],
            'values' => ['required', 'array'],
        ]);
        $admin->save($data['namespace'], $data['values']);

        return back()->with('success', 'Đã lưu cấu hình.');
    }
}
