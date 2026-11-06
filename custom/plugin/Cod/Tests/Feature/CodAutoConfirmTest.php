<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    config(['vani.bank-transfer.account' => ['bank' => 'VCB', 'account_number' => '0123456789', 'account_name' => 'VANI']]);
    ['s' => $this->s] = C::store(stock: 50);
    $this->place = function (string $method = 'cod', string $phone = '0912 345 678'): Order {
        $api = '/api/storefront/v1';
        $created = $this->postJson("{$api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => 330_000, 'contact' => ['phone' => $phone]]), [...$headers, 'Idempotency-Key' => 'k-'.$cart])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->first();
    };
    $this->mode = fn (string $mode, int $minimum = 1) => T::seed(function () use ($mode, $minimum) {
        app(Settings::class)->set('vani.cod', 'auto_confirm_mode', $mode);
        app(Settings::class)->set('vani.cod', 'trusted_min_delivered', $minimum);
    });
    $this->confirmReason = fn (Order $order) => DB::table('order_events')->where('order_id', $order->id)->where('to_status', 'confirmed')->value('reason');
});

it('chưa chọn chế độ: theo công tắc cũ auto_confirm / VANI_COD_AUTO_CONFIRM (mặc định bật = xác nhận mọi đơn)', function () {
    expect(($this->confirmReason)(($this->place)()))->toBe('cod_auto_confirm');

    T::seed(fn () => app(Settings::class)->set('vani.cod', 'auto_confirm', false));
    expect(($this->place)()->order_status->value)->toBe('pending');
});

it('off: CSKH gọi xác nhận mọi đơn COD', function () {
    ($this->mode)('off');
    expect(($this->place)()->order_status->value)->toBe('pending');
});

it('all: mọi đơn COD được xác nhận ngay lúc đặt; đơn chuyển khoản vẫn chờ thanh toán', function () {
    ($this->mode)('all');
    $cod = ($this->place)();

    expect(($this->confirmReason)($cod))->toBe('cod_auto_confirm')
        ->and(($this->place)('manual_bank_transfer')->order_status->value)->toBe('pending');
});

it('trusted: chỉ SĐT đã có đủ đơn giao thành công và chưa từng bị hoàn về', function () {
    ($this->mode)('trusted', 2);
    $first = ($this->place)();
    expect($first->order_status->value)->toBe('pending');

    // Một đơn đã giao: chưa đủ 2. (Đơn tự xác nhận được đánh dấu giao/hoàn bằng DB để dựng lịch sử.)
    DB::table('orders')->where('id', $first->id)->update(['fulfillment_status' => 'delivered']);
    expect(($this->place)()->order_status->value)->toBe('pending');

    $second = Order::query()->withoutGlobalScopes()->latest('id')->first();
    DB::table('orders')->where('id', $second->id)->update(['fulfillment_status' => 'delivered']);
    $trusted = ($this->place)();
    expect(($this->confirmReason)($trusted))->toBe('cod_auto_confirm_trusted')
        // SĐT khác: khách mới, chờ gọi.
        ->and(($this->place)('cod', '0987 000 111')->order_status->value)->toBe('pending');

    // Từng bị hoàn về → không còn là khách quen.
    DB::table('orders')->where('id', $trusted->id)->update(['fulfillment_status' => 'returned_to_sender']);
    expect(($this->place)()->order_status->value)->toBe('pending');
});
