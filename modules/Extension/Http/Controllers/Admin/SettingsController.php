<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Settings\SettingsAdmin;
use Modules\Extension\Contracts\SettingActions;
use Modules\Tenancy\Contracts\Data\SettingGroup;

/**
 * Admin → Cài đặt: "Cài đặt hệ thống" (namespace `core`, chia nhóm nhỏ theo SettingGroup — mỗi nhóm một trang) và
 * "Cài đặt Plugin" (namespace = plugin id, mỗi plugin một trang).
 */
final class SettingsController
{
    public function __construct(
        private readonly SettingsAdmin $admin,
        private readonly ManifestRepository $manifests,
        private readonly SettingActions $actions,
    ) {}

    public function index(): Response
    {
        abort_unless($this->admin->canOpenAny(), 403);

        return Inertia::render('Extension::Settings/Index', [
            'system' => array_map(fn (array $group): array => [
                ...$this->groupLink($group['group']),
                'description' => __("extension::texts.setting_groups.{$group['group']->value}.description"),
                'labels' => $group['labels'],
                'count' => $group['count'],
                'customized' => $group['customized'],
            ], $this->manageableGroups()),
            'plugins' => array_map(fn (string $namespace): array => [
                ...$this->pluginLink($namespace),
                ...$this->admin->summary($namespace),
            ], $this->manageablePlugins()),
        ]);
    }

    /** Một nhóm của Cài đặt hệ thống. */
    public function system(string $group): Response
    {
        $current = SettingGroup::tryFrom($group) ?? abort(404);
        abort_unless($this->admin->canManage('core', $current), 403);
        $fields = $this->admin->form('core', $current);
        abort_if($fields === [], 404);

        return $this->page('core', $this->groupLink($current) + [
            'description' => __("extension::texts.setting_groups.{$current->value}.description"),
        ], $fields, "system:{$current->value}", $this->actions->for($current));
    }

    /** Cấu hình của một plugin. `core` → nhóm đầu tiên của Cài đặt hệ thống. */
    public function show(string $namespace): Response|RedirectResponse
    {
        if ($namespace === 'core') {
            $first = $this->manageableGroups()[0]['group'] ?? abort(403);

            return redirect()->route('admin.settings.system', $first->value);
        }
        abort_unless(in_array($namespace, $this->admin->pluginNamespaces(), true), 404);
        abort_unless($this->admin->canManage($namespace), 403);

        return $this->page($namespace, $this->pluginLink($namespace) + ['description' => $namespace], $this->admin->form($namespace), "plugin:{$namespace}");
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'namespace' => ['required', 'string', 'max:64'],
            'values' => ['required', 'array'],
        ]);
        $this->admin->authorizeSave($data['namespace'], $data['values']);
        $this->admin->save($data['namespace'], $data['values']);

        return back()->with('success', __('extension::texts.settings_controller.settings_saved'));
    }

    /**
     * @param  array<string, string>  $section  code, title, url, description (+ icon / namespace)
     * @param  list<array<string, mixed>>  $fields
     * @param  list<array{label: string, url: string}>  $actions  nút thao tác của nhóm (SettingActions)
     */
    private function page(string $namespace, array $section, array $fields, string $active, array $actions = []): Response
    {
        return Inertia::render('Extension::Settings/Show', [
            'baseUrl' => route('admin.settings.index'),
            'action' => route('admin.settings.update'),
            'namespace' => $namespace,
            'section' => $section,
            'fields' => $fields,
            'active' => $active,
            'actions' => $actions,
            'nav' => [
                'system' => array_map(fn (array $group): array => $this->groupLink($group['group']), $this->manageableGroups()),
                'plugins' => array_map(fn (string $plugin): array => $this->pluginLink($plugin), $this->manageablePlugins()),
            ],
        ]);
    }

    /**
     * Nhóm Cài đặt hệ thống người dùng sửa được.
     *
     * @return list<array{group: SettingGroup, labels: list<string>, count: int, customized: int}>
     */
    private function manageableGroups(): array
    {
        return array_values(array_filter($this->admin->systemGroups(), fn (array $group): bool => $this->admin->canManage('core', $group['group'])));
    }

    /**
     * @return list<string>
     */
    private function manageablePlugins(): array
    {
        return array_values(array_filter($this->admin->pluginNamespaces(), fn (string $namespace): bool => $this->admin->canManage($namespace)));
    }

    /**
     * @return array{code: string, icon: string, title: string, url: string}
     */
    private function groupLink(SettingGroup $group): array
    {
        return [
            'code' => "system:{$group->value}",
            'icon' => $group->value,
            'title' => __("extension::texts.setting_groups.{$group->value}.title"),
            'url' => route('admin.settings.system', $group->value),
        ];
    }

    /**
     * @return array{code: string, namespace: string, title: string, url: string}
     */
    private function pluginLink(string $namespace): array
    {
        return [
            'code' => "plugin:{$namespace}",
            'namespace' => $namespace,
            'title' => $this->manifests->find($namespace)?->displayName(app()->getLocale()) ?? "Plugin {$namespace}",
            'url' => route('admin.settings.show', $namespace),
        ];
    }
}
