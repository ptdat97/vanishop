<?php

declare(strict_types=1);

namespace Plugin\HelloWorld;

use Modules\Extension\PluginServiceProvider;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\StorefrontEnricher;
use Plugin\HelloWorld\Infrastructure\HelloEnricher;

/**
 * Plugin mẫu: minh hoạ slot hook (Admin + storefront), làm giàu dữ liệu storefront, menu Admin, permission và trang Admin.
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

        // Storefront: dữ liệu (StorefrontEnricher) + hiển thị qua slot bằng view của plugin.
        $this->contribute(StorefrontEnricher::TAG, HelloEnricher::class);
        $this->loadViewsFrom($this->pluginPath('Resources/views'), 'vani-hello-world');
        $this->onSlot('vani.storefront.pdp.after_title', fn (array $product): ?SlotView => isset($product['extensions']['vani.hello-world'])
            ? new SlotView('vani-hello-world::greeting', $product['extensions']['vani.hello-world'])
            : null);

        $this->adminMenu('hello-world', 'Hello World', 'admin.plugins.vani-hello-world.index', 'hello-world.view');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('HelloWorld', $this->pluginPath('Resources/js/Pages'));
    }
}
