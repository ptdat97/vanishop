<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Mục tiêu G8 (vision §3, R36): một đơn COD thường đi từ "Cần xác nhận" tới "Đang giao" chỉ trên Tổng quan → danh sách
| đơn → trang đơn. Nhân viên không mở trang nào khác: mọi thao tác lấy từ URL mà chính các trang đó đưa ra (thẻ trạng
| thái, thẻ Giao hàng). Test ghi lại mọi trang đã mở và chặn trang ngoài ba trang trên.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 20);
    // Cửa hàng chọn "CSKH gọi xác nhận mọi đơn": đơn COD dừng ở "Cần xác nhận".
    T::seed(fn () => app(Settings::class)->set('vani.cod', 'auto_confirm_mode', 'off'));

    $api = '/api/storefront/v1';
    $created = $this->postJson("{$api}/carts")->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $cart = $created->json('data.id');
    $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
    $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => 'cod', 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'k-'.$cart])->assertCreated();
    $this->placed = Order::query()->withoutGlobalScopes()->latest('id')->firstOrFail();

    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage', 'fulfillment.view', 'fulfillment.manage']), 'staff');
    $this->opened = [];
    $this->open = function (string $url): array {
        $this->opened[] = (string) parse_url($url, PHP_URL_PATH);

        return $this->get($url)->assertOk()->viewData('page');
    };
    $this->act = fn (array $action, array $input = []): TestResponse => $this->post($action['url'], [...($action['data'] ?? []), ...$input])->assertRedirect()->assertSessionHasNoErrors();
    $this->primary = fn (array $props, string $panel): array => collect(collect($props['panels'])->firstWhere('title', $panel)['actions'])->firstWhere('primary', true);
});

it('G8: đơn COD từ "Cần xác nhận" tới "Đang giao" chỉ qua Tổng quan, danh sách đơn và trang đơn', function () {
    // 1. Tổng quan: ô việc "Đơn cần xác nhận" có số đếm và dẫn thẳng tới danh sách đã lọc.
    $task = collect(($this->open)('/admin')['props']['tasks'])->firstWhere('key', 'orders.to_confirm');
    expect($task['count'])->toBe(1);

    // 2. Danh sách đơn đã lọc: thấy đơn, mở trang đơn.
    $list = ($this->open)($task['url']);
    expect($list['component'])->toBe('Ordering::Orders/Index')
        ->and($list['props']['orders'])->toHaveCount(1)
        ->and($list['props']['orders'][0]['stage'])->toBe('to_confirm');
    $orderUrl = "/admin/orders/orders/{$list['props']['orders'][0]['id']}";

    // 3. Trang đơn: bước tiếp theo là "Xác nhận đơn".
    $props = ($this->open)($orderUrl)['props'];
    $confirm = collect($props['statusCard']['actions'])->firstWhere('primary', true);
    expect($props['stage'])->toBe('to_confirm')->and($confirm['label'])->toBe('Xác nhận đơn');
    ($this->act)($confirm);

    // 4. Xác nhận xong: vận đơn tự tạo, bước tiếp theo ngay trên trang đơn là nhập mã vận đơn của hãng.
    $props = ($this->open)($orderUrl)['props'];
    $book = ($this->primary)($props, 'Giao hàng');
    expect($props['stage'])->toBe('to_ship')->and($book['label'])->toBe('Nhập mã vận đơn');
    ($this->act)($book, ['tracking_number' => 'SPX0001', 'service_code' => 'SPX']);

    // 5. Bàn giao cho hãng → đơn "Đang giao".
    $handover = ($this->primary)(($this->open)($orderUrl)['props'], 'Giao hàng');
    expect($handover['label'])->toBe('Đã bàn giao cho hãng');
    ($this->act)($handover);

    $props = ($this->open)($orderUrl)['props'];
    expect($props['stage'])->toBe('shipping')
        ->and($this->placed->fresh()->order_status->value)->toBe('processing')
        ->and(DB::table('stock_levels')->where('variant_id', $this->s->id)->value('on_hand'))->toEqual(19);

    // Chỉ ba trang được mở: Tổng quan, danh sách đơn, trang đơn.
    expect(array_values(array_unique($this->opened)))->toBe(['/admin', '/admin/orders/orders', $orderUrl]);
});

it('G8: việc hằng ngày làm hàng loạt từ danh sách đơn — xác nhận nhiều đơn, không mở từng đơn', function () {
    $list = ($this->open)('/admin/orders/orders?stage=to_confirm')['props'];
    $ids = array_column($list['orders'], 'id');

    $this->post('/admin/orders/orders/bulk-confirm', ['ids' => $ids])->assertRedirect()->assertSessionHas('success');

    $list = ($this->open)('/admin/orders/orders?stage=to_ship')['props'];
    expect(array_column($list['orders'], 'id'))->toBe($ids)
        ->and($list['stageCounts']['to_confirm'])->toBe(0)
        ->and(array_values(array_unique($this->opened)))->toBe(['/admin/orders/orders']);
});
