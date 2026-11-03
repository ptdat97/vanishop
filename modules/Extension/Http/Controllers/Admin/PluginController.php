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
use Modules\Identity\Contracts\Data\ScopeRef;
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
            'plugins' => $overview->rows(app()->getLocale()),
            'kinds' => PluginManifest::KINDS,
            'invalid' => array_values($manifests->invalid()),
        ]);
    }

    public function show(string $plugin, PluginOverview $overview): Response
    {
        Gate::authorize('extension.plugins.view');
        $detail = $overview->detail($plugin, app()->getLocale());
        abort_if($detail === null, 404);

        return Inertia::render('Extension::Plugins/Show', [
            'baseUrl' => route('admin.plugins.index'),
            'plugin' => $detail,
            'canManage' => Gate::allows('extension.plugins.manage'),
            'canConfigure' => Gate::allows('settings.manage', [ScopeRef::owner()]),
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
        return $this->run(fn () => $plugins->install($plugin), "Đã cài {$plugin}. Bật plugin để bắt đầu dùng.");
    }

    public function enable(string $plugin, PluginManager $plugins): RedirectResponse
    {
        return $this->run(fn () => $plugins->enable($plugin), "Đã bật {$plugin}.");
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
            'drain' => $this->run(fn () => $plugins->drain($plugin), "Đã ngừng {$plugin}: không nhận giao dịch mới, tự tắt khi xử lý xong việc dở dang."),
            'force' => $this->run(fn () => $plugins->disable($plugin, force: true), "Đã tắt ngay {$plugin}. Việc dở dang cần xử lý tay."),
            default => $this->run(fn () => $plugins->disable($plugin), "Đã tắt {$plugin}."),
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
        ], ['confirm_id.in' => 'Gõ đúng id plugin để xác nhận xoá dữ liệu.']);
        $purge = (bool) ($data['purge'] ?? false);

        return $this->run(
            fn () => $plugins->uninstall($plugin, $purge, $purge && (bool) ($data['drop_retained'] ?? false)),
            $purge ? "Đã gỡ {$plugin} và xoá dữ liệu của plugin." : "Đã gỡ {$plugin}. Dữ liệu của plugin được giữ lại.",
            redirectToIndex: true,
        );
    }

    public function upgrade(string $plugin, PluginManager $plugins): RedirectResponse
    {
        return $this->run(fn () => $plugins->upgrade($plugin), "Đã nâng cấp {$plugin}.");
    }

    public function settings(string $plugin, SettingsAdmin $admin, ManifestRepository $manifests): Response
    {
        Gate::authorize('settings.manage', [ScopeRef::owner()]);
        $manifest = $manifests->find($plugin);
        abort_if($manifest === null || ! in_array($plugin, $admin->namespaces(), true), 404);

        return Inertia::render('Extension::Plugins/Settings', [
            'baseUrl' => route('admin.plugins.index'),
            'plugin' => ['id' => $plugin, 'name' => $manifest->displayName(app()->getLocale())],
            'fields' => $admin->form($plugin),
        ]);
    }

    public function updateSettings(string $plugin, Request $request, SettingsAdmin $admin): RedirectResponse
    {
        Gate::authorize('settings.manage', [ScopeRef::owner()]);
        abort_unless(in_array($plugin, $admin->namespaces(), true), 404);
        $admin->save($plugin, (array) $request->validate(['values' => ['required', 'array']])['values']);

        return back()->with('success', 'Đã lưu cấu hình.');
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
