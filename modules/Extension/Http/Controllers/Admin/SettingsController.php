<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Brand\Contracts\BrandDirectory;
use Modules\Brand\Contracts\Data\BrandData;
use Modules\Channel\Contracts\ChannelDirectory;
use Modules\Extension\Application\Settings\SettingsAdmin;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Tenancy\Contracts\Data\SettingsScope;

/**
 * Cấu hình theo phạm vi (owner/brand/kênh) cho Core (`core`) và plugin (namespace = plugin id). Cấp Owner.
 */
final class SettingsController
{
    public function index(Request $request, SettingsAdmin $admin, BrandDirectory $brands, ChannelDirectory $channels): Response
    {
        Gate::authorize('settings.manage', [ScopeRef::owner()]);
        $namespaces = $admin->namespaces();
        $namespace = in_array($request->query('namespace'), $namespaces, true) ? (string) $request->query('namespace') : ($namespaces[0] ?? 'core');
        [$scopeType, $scopeId] = $this->scope((string) $request->query('scope', SettingsScope::OWNER));

        return Inertia::render('Extension::Settings/Index', [
            'baseUrl' => route('admin.settings.index'),
            'namespaces' => $namespaces,
            'namespace' => $namespace,
            'scope' => $scopeType === SettingsScope::OWNER ? SettingsScope::OWNER : "{$scopeType}:{$scopeId}",
            'scopes' => [
                ['value' => SettingsScope::OWNER, 'label' => 'Toàn Owner (mặc định)'],
                ...array_map(fn (BrandData $brand): array => ['value' => "brand:{$brand->id}", 'label' => "Brand: {$brand->name}"], $brands->list(null)),
                ...array_map(fn (array $channel): array => ['value' => "channel:{$channel['id']}", 'label' => "Kênh: {$channel['name']}"], $channels->all()),
            ],
            'fields' => $admin->form($namespace, $scopeType, $scopeId),
        ]);
    }

    public function update(Request $request, SettingsAdmin $admin): RedirectResponse
    {
        Gate::authorize('settings.manage', [ScopeRef::owner()]);
        $data = $request->validate([
            'namespace' => ['required', 'string', 'max:64'],
            'scope' => ['required', 'string', 'max:40'],
            'values' => ['required', 'array'],
        ]);
        [$scopeType, $scopeId] = $this->scope($data['scope']);
        $admin->save($data['namespace'], $scopeType, $scopeId, $data['values']);

        return back()->with('success', 'Đã lưu cấu hình.');
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function scope(string $value): array
    {
        if (preg_match('/^(brand|channel|legal_entity):(\d+)$/', $value, $match) === 1) {
            return [$match[1], (int) $match[2]];
        }

        return [SettingsScope::OWNER, 0];
    }
}
