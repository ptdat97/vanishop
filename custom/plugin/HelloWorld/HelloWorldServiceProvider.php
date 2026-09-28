<?php

declare(strict_types=1);

namespace Plugin\HelloWorld;

use Modules\Extension\PluginServiceProvider;

/**
 * Plugin mẫu: minh hoạ slot hook, menu Admin, permission và trang Admin của plugin.
 * Chỉ dùng API public của Core (Modules\Extension\PluginServiceProvider).
 */
final class HelloWorldServiceProvider extends PluginServiceProvider
{
    protected function pluginId(): string
    {
        return 'vani.hello-world';
    }

    public function boot(): void
    {
        $this->permissions(['hello-world.view' => 'Xem trang Hello World']);

        $this->onSlot('vani.admin.dashboard.cards', fn (): array => [
            'title' => 'Hello World',
            'body' => 'Card này do plugin vani.hello-world thêm vào qua hook, không sửa Core.',
        ]);

        $this->adminMenu('hello-world', 'Hello World', 'admin.plugins.vani-hello-world.index', 'hello-world.view');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('HelloWorld', $this->pluginPath('Resources/js/Pages'));
    }
}
