<?php

declare(strict_types=1);

namespace Plugin\HelloWorld;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Cart\Contracts\CartLineOption;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Extension\PluginServiceProvider;
use Modules\Ordering\Contracts\ConfirmationChannel;
use Modules\Ordering\Contracts\PackingSlipTemplate;
use Modules\Payment\Contracts\RefundPayout;
use Modules\Shared\Support\SpreadsheetRows;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\StorefrontBlock;
use Modules\Storefront\Contracts\StorefrontEnricher;
use Plugin\HelloWorld\Infrastructure\GiftMessageOption;
use Plugin\HelloWorld\Infrastructure\HelloBannerBlock;
use Plugin\HelloWorld\Infrastructure\HelloConfirmationChannel;
use Plugin\HelloWorld\Infrastructure\HelloEnricher;
use Plugin\HelloWorld\Infrastructure\HelloPayout;
use Plugin\HelloWorld\Infrastructure\HelloSlipA6;

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
        // Chuỗi giao diện storefront của plugin theo ngôn ngữ (khoá = câu tiếng Việt, ADR-038); theme ghi đè được.
        $this->translations($this->pluginPath('Resources/lang'), 'vani-hello-world');
        $this->permissions(['hello-world.view' => 'Xem trang Hello World']);
        $this->publishHooks($this->pluginPath('hooks.php'));

        $this->onSlot('vani.admin.dashboard.cards', fn (): array => [
            'title' => 'Hello World',
            'body' => 'Card này do plugin vani.hello-world thêm vào qua hook, không sửa Core.',
        ]);

        // Storefront: dữ liệu (StorefrontEnricher) + hiển thị qua slot bằng view của plugin.
        $this->contribute(StorefrontEnricher::TAG, HelloEnricher::class);
        $this->contribute(StorefrontBlock::TAG, HelloBannerBlock::class);
        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-hello-world');
        $this->storefrontRoutes($this->pluginPath('Http/routes/storefront-api.php'));
        $this->storefrontPages($this->pluginPath('Http/routes/storefront-pages.php'));
        $this->accountPage('hello', 'vani-hello-world::storefront.account_page', 'storefront.p.vani-hello-world.index');
        $this->onSlot('vani.storefront.pdp.after_title', fn (array $product): ?SlotView => isset($product['extensions']['vani.hello-world'])
            ? new SlotView('vani-hello-world::greeting', $product['extensions']['vani.hello-world'])
            : null);

        $this->extendCoreAdminScreens();
        $this->contribute(ConfirmationChannel::TAG, HelloConfirmationChannel::class);
        // View Admin (chỉ tiếng Việt như mẫu phiếu Core) — tách khỏi view storefront có bản dịch.
        $this->loadViewsFrom($this->pluginPath('Resources/admin-views'), 'vani-hello-world-admin');
        $this->contribute(PackingSlipTemplate::TAG, HelloSlipA6::class);
        $this->contribute(RefundPayout::TAG, HelloPayout::class);

        // Cú pháp ngắn (ADR-031): listener vẫn thuộc vani.hello-world (suy ra từ vị trí file) → tắt plugin là tắt.
        vani_add_filter('vani.admin.page.catalog.products.index', fn (array $props): array => [...$props, 'helloWorld' => ['banner' => 'Xin chào từ plugin']]);

        // Luồng giao dịch (W4): tuỳ chọn dòng giỏ + thuộc tính ngữ cảnh khuyến mãi từ trường checkout extra[vani.hello-world][ref].
        $this->contribute(CartLineOption::TAG, GiftMessageOption::class);
        $this->onSlot('vani.storefront.pdp.add_to_cart_fields', fn (): SlotView => new SlotView('vani-hello-world::gift-message-field'));
        $this->onFilter('vani.checkout.context', function (array $attributes, CheckoutRequest $request): array {
            $ref = trim((string) ($request->extra['vani.hello-world']['ref'] ?? ''));

            return $ref === '' ? $attributes : [...$attributes, 'vani.hello-world.ref' => $ref];
        });

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
        $this->extendOperationScreens();
    }

    /**
     * Implementation tham chiếu của EP v3 X1 (R26): "Đánh dấu Hello" trên vận đơn, đổi trả, thanh toán, khuyến mãi, tồn kho
     * và bảng giá — thao tác hàng loạt ghi vào bảng riêng, cột + bộ lọc đọc lại, công cụ nhập danh sách id từ file, tab trên
     * trang đổi trả, bộ lọc phụ của SkuPicker. Tồn kho, bảng giá, SkuPicker dùng chung id SKU (`variant`).
     */
    private function extendOperationScreens(): void
    {
        $flags = fn () => DB::table('plg_hello_world_flags');
        $flagged = fn (string $subject): array => $flags()->where('subject', $subject)->pluck('subject_id')->map(fn ($id): int => (int) $id)->all();
        $flag = function (string $subject, int $id) use ($flags): void {
            $flags()->insertOrIgnore(['subject' => $subject, 'subject_id' => $id, 'created_at' => now()]);
        };
        $subjects = ['shipment' => 'shipment', 'return' => 'return', 'payment' => 'payment', 'promotion' => 'promotion', 'stock' => 'variant', 'price_list_item' => 'variant'];

        foreach ($subjects as $resource => $subject) {
            $this->adminColumn($resource, 'flag', 'Hello',
                resolve: fn (array $ids): array => array_fill_keys(array_values(array_intersect($ids, $flagged($subject))), '★ Đã đánh dấu'),
            );
            $this->adminFilter($resource, 'flagged', 'Hello', ['yes' => 'Đã đánh dấu'], apply: fn (string $value): array => $flagged($subject));
            $this->adminAction($resource, 'flag', 'Đánh dấu Hello', 'hello-world.view',
                handle: function (int $id) use ($flag, $subject): ?string {
                    $flag($subject, $id);

                    return null;
                }, scope: 'bulk',
            );
            $this->adminTool($resource, 'import_flags', 'Nhập đánh dấu Hello', 'hello-world.view',
                handle: function (UploadedFile $file, array $fields) use ($flag, $subject): string {
                    $ids = array_values(array_unique(array_filter(array_map(fn (array $row): int => (int) ($row[0] ?? 0), SpreadsheetRows::fromUpload($file)), fn (int $id): bool => $id > 0)));
                    if ($ids === []) {
                        throw ValidationException::withMessages(['file' => 'File không có id nào ở cột đầu.']);
                    }
                    foreach ($ids as $id) {
                        $flag($subject, $id);
                    }

                    return 'Đã đánh dấu '.count($ids).' mục'.(($fields['note'] ?? '') !== '' ? " ({$fields['note']})" : '').'.';
                },
                help: 'File .csv/.xlsx: mỗi dòng một id ở cột đầu.', fields: [['key' => 'note', 'label' => 'Ghi chú', 'placeholder' => 'tuỳ chọn']],
            );
        }
        $this->adminTab('return', 'hello', 'Hello World',
            rows: fn (int $returnId): array => [['label' => 'Đánh dấu', 'value' => in_array($returnId, $flagged('return'), true) ? 'Có' : 'Không']],
        );
        $this->adminAction('return', 'flag_one', 'Đánh dấu Hello', 'hello-world.view', handle: function (int $id) use ($flag): string {
            $flag('return', $id);

            return 'Đã đánh dấu Hello.';
        });
        $this->adminFilter('variant', 'flagged', 'Hello', ['yes' => 'Đã đánh dấu'], apply: fn (string $value): array => $flagged('variant'));
    }
}
