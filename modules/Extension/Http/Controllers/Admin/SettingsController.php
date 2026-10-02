<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Extension\Application\Settings\SettingsAdmin;
use Modules\Identity\Contracts\Data\ScopeRef;

/**
 * Cấu hình của cửa hàng cho Core (`core`) và plugin (namespace = plugin id).
 */
final class SettingsController
{
    public function index(Request $request, SettingsAdmin $admin): Response
    {
        Gate::authorize('settings.manage', [ScopeRef::owner()]);
        $namespaces = $admin->namespaces();
        $namespace = in_array($request->query('namespace'), $namespaces, true) ? (string) $request->query('namespace') : ($namespaces[0] ?? 'core');

        return Inertia::render('Extension::Settings/Index', [
            'baseUrl' => route('admin.settings.index'),
            'namespaces' => $namespaces,
            'namespace' => $namespace,
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
