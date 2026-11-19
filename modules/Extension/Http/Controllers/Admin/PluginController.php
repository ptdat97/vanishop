<?php

declare(strict_types=1);

namespace Modules\Extension\Http\Controllers\Admin;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Extension\Application\Plugins\ManifestRepository;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Application\Plugins\PluginOperationFailed;
use Modules\Extension\Application\Plugins\PluginOverview;
use Modules\Extension\Application\Settings\SettingsAdmin;
use Modules\Extension\Domain\Plugin\PluginManifest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Admin → Plugin (plugin-system §5b): danh sách, chi tiết, cài/bật/tắt/gỡ/nâng cấp và cấu hình riêng từng plugin.
 * Thao tác gọi PluginManager — cùng kiểm tra và audit với `vani:plugin:*`.
 */
final class PluginController
{
    public function index(PluginOverview $overview, ManifestRepository $manifests): Response
    {
        Gate::authorize('extension.plugins.view');

        return Inertia::render('Extension::Plugins/Index', [
            'baseUrl' => route('admin.plugins.index'),
            'labels' => __('extension::labels'),
            'plugins' => $overview->rows(app()->getLocale()),
            'kinds' => PluginManifest::KINDS,
            'invalid' => array_values($manifests->invalid()),
        ]);
    }

    public function show(string $plugin, PluginOverview $overview, SettingsAdmin $settings): Response
    {
        Gate::authorize('extension.plugins.view');
        $detail = $overview->detail($plugin, app()->getLocale());
        abort_if($detail === null, 404);

        return Inertia::render('Extension::Plugins/Show', [
            'baseUrl' => route('admin.plugins.index'),
            'labels' => __('extension::labels'),
            'plugin' => $detail,
            'canManage' => Gate::allows('extension.plugins.manage'),
            'canConfigure' => $settings->canManage($plugin),
        ]);
    }

    /**
     * Icon khai báo trong manifest. SVG có thể chứa script → chặn mọi tài nguyên/script bằng CSP; Admin chỉ hiển thị qua <img>.
     */
    public function icon(string $plugin, ManifestRepository $manifests): BinaryFileResponse
    {
        Gate::authorize('extension.plugins.view');
        $path = $manifests->find($plugin)?->iconPath();
        abort_if($path === null || filesize($path) > 262_144, 404);

        return response()->file($path, [
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'; sandbox",
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }

    public function install(string $plugin, PluginManager $plugins): RedirectResponse
    {
        return $this->run(fn () => $plugins->install($plugin), __('extension::texts.plugin_controller.installed_plugin_enable_plugin_start', ['plugin' => $plugin]));
    }

    public function enable(string $plugin, PluginManager $plugins): RedirectResponse
    {
        return $this->run(fn () => $plugins->enable($plugin), __('extension::texts.plugin_controller.enabled_plugin', ['plugin' => $plugin]));
    }

    /**
     * `mode`: `normal` (chặn khi còn việc dở dang), `drain` (ngừng nhận giao dịch mới, tự tắt khi xong), `force` (tắt ngay —
     * phải tick xác nhận, audit ghi việc dở dang).
     */
    public function disable(string $plugin, Request $request, PluginManager $plugins): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:normal,drain,force'],
            'confirm_force' => ['exclude_unless:mode,force', 'accepted'],
        ]);

        return match ($data['mode']) {
            'drain' => $this->run(fn () => $plugins->drain($plugin), __('extension::texts.plugin_controller.stopping_plugin_no_new_transactions', ['plugin' => $plugin])),
            'force' => $this->run(fn () => $plugins->disable($plugin, force: true), __('extension::texts.plugin_controller.disabled_plugin_immediately_pending_work', ['plugin' => $plugin])),
            default => $this->run(fn () => $plugins->disable($plugin), __('extension::texts.plugin_controller.disabled_plugin', ['plugin' => $plugin])),
        };
    }

    /**
     * Gỡ; `purge` xoá bảng dữ liệu của plugin (rollback migration) — phải gõ lại đúng id plugin để xác nhận.
     */
    public function uninstall(string $plugin, Request $request, PluginManager $plugins): RedirectResponse
    {
        $data = $request->validate([
            'purge' => ['boolean'],
            'drop_retained' => ['boolean'],
            'confirm_id' => ['exclude_unless:purge,true', 'required', 'in:'.$plugin],
        ], ['confirm_id.in' => __('extension::texts.plugin_controller.type_exact_plugin_id_confirm')]);
        $purge = (bool) ($data['purge'] ?? false);

        return $this->run(
            fn () => $plugins->uninstall($plugin, $purge, $purge && (bool) ($data['drop_retained'] ?? false)),
            $purge ? __('extension::texts.plugin_controller.uninstalled_plugin_deleted_data', ['plugin' => $plugin]) : __('extension::texts.plugin_controller.uninstalled_plugin_plugin_s_data', ['plugin' => $plugin]),
            redirectToIndex: true,
        );
    }

    public function upgrade(string $plugin, PluginManager $plugins): RedirectResponse
    {
        return $this->run(fn () => $plugins->upgrade($plugin), __('extension::texts.plugin_controller.upgraded_plugin', ['plugin' => $plugin]));
    }

    public function settings(string $plugin, SettingsAdmin $admin, ManifestRepository $manifests): Response
    {
        $manifest = $manifests->find($plugin);
        abort_if($manifest === null || ! in_array($plugin, $admin->namespaces(), true), 404);
        abort_unless($admin->canManage($plugin), 403);

        return Inertia::render('Extension::Plugins/Settings', [
            'baseUrl' => route('admin.plugins.index'),
            'plugin' => ['id' => $plugin, 'name' => $manifest->displayName(app()->getLocale())],
            'fields' => $admin->form($plugin),
        ]);
    }

    public function updateSettings(string $plugin, Request $request, SettingsAdmin $admin): RedirectResponse
    {
        abort_unless(in_array($plugin, $admin->namespaces(), true), 404);
        $values = (array) $request->validate(['values' => ['required', 'array']])['values'];
        $admin->authorizeSave($plugin, $values);
        $admin->save($plugin, $values);

        return back()->with('success', __('extension::texts.plugin_controller.settings_saved'));
    }

    private function run(Closure $operation, string $success, bool $redirectToIndex = false): RedirectResponse
    {
        Gate::authorize('extension.plugins.manage');

        try {
            $operation();
        } catch (PluginOperationFailed $exception) {
            return back()->withErrors(['business' => $exception->getMessage()]);
        }

        return ($redirectToIndex ? redirect()->route('admin.plugins.index') : back())->with('success', $success);
    }
}
