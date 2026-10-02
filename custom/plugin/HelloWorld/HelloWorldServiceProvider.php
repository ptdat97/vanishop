<?php

declare(strict_types=1);

namespace Plugin\HelloWorld;

use Illuminate\Support\Facades\DB;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Extension\PluginServiceProvider;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\StorefrontEnricher;
use Plugin\HelloWorld\Infrastructure\HelloEnricher;

/**
 * Plugin mẫu: minh hoạ slot hook (Admin + storefront), làm giàu dữ liệu storefront, mở rộng màn hình Admin của Core
 * (phần form, cột, bộ lọc, thao tác, tab — ADR-030), menu Admin, permission và trang Admin.
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
        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-hello-world');
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));
        $this->storefrontPages($this->pluginPath('Http/routes/storefront-pages.php'));
        $this->onSlot('vani.storefront.pdp.after_title', fn (array $product): ?SlotView => isset($product['extensions']['vani.hello-world'])
            ? new SlotView('vani-hello-world::greeting', $product['extensions']['vani.hello-world'])
            : null);

        $this->extendCoreAdminScreens();

        $this->adminMenu('hello-world', 'Hello World', 'admin.plugins.vani-hello-world.index', 'hello-world.view');
        $this->adminRoutes($this->pluginPath('Http/routes/admin.php'));
        $this->adminPages('HelloWorld', $this->pluginPath('Resources/js/Pages'));
    }

    /**
     * Implementation tham chiếu của registry Admin (R26). Dữ liệu nằm trong bảng riêng plg_hello_world_notes.
     */
    private function extendCoreAdminScreens(): void
    {
        $notes = fn () => DB::table('plg_hello_world_notes');

        $this->adminFormSection('product', 'note', 'Hello World',
            fields: [FieldDefinition::string('note', 'Ghi chú Hello', help: 'Lưu trong bảng riêng của plugin.', max: 255)],
            load: fn (int $styleId): array => ['note' => $notes()->where('style_id', $styleId)->value('note')],
            save: function (int $styleId, array $values) use ($notes): void {
                $note = trim((string) ($values['note'] ?? ''));
                $note === ''
                    ? $notes()->where('style_id', $styleId)->delete()
                    : $notes()->updateOrInsert(['style_id' => $styleId], ['note' => $note, 'updated_at' => now(), 'created_at' => now()]);
            },
        );
        $this->adminColumn('product', 'note', 'Ghi chú Hello',
            resolve: fn (array $styleIds): array => $notes()->whereIn('style_id', $styleIds)->pluck('note', 'style_id')->all(),
        );
        $this->adminFilter('product', 'has_note', 'Ghi chú Hello', ['yes' => 'Có ghi chú'],
            apply: fn (string $value): array => $notes()->pluck('style_id')->map(fn ($id): int => (int) $id)->all(),
        );
        $this->adminAction('order', 'greet', 'Gửi lời chào', 'hello-world.view',
            handle: fn (int $orderId): string => "Đã gửi lời chào cho đơn #{$orderId}.", scope: 'both',
        );
        $this->adminTab('customer', 'hello', 'Hello World',
            rows: fn (int $customerId): array => [['label' => 'Lời chào', 'value' => "Xin chào khách #{$customerId}"]],
        );
    }
}
