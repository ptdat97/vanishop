<?php

use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/CustomerTestHelpers.php';

beforeEach(function () {
    H::fakeOtp();
    ['brand' => $this->brand, 's' => $this->s, 'm' => $this->m] = C::store();
    $this->guestCart = function (int $variantId, int $quantity = 1): array {
        $created = $this->postJson(H::API.'/carts', [], H::CHANNEL)->assertCreated();
        $token = $created->json('meta.token');
        $this->postJson(H::API."/carts/{$created->json('data.id')}/lines", ['variant_id' => $variantId, 'quantity' => $quantity], [...H::CHANNEL, 'X-Vani-Cart-Token' => $token])->assertOk();

        return [$created->json('data.id'), $token];
    };
});

it('đăng nhập kèm giỏ vãng lai: chưa có giỏ → giỏ thành của khách (token cũ vô hiệu); truy cập bằng phiên, không cần token', function () {
    [$cartId, $cartToken] = ($this->guestCart)($this->s->id);

    $token = H::login($this, extra: ['cart_id' => $cartId], headers: ['X-Vani-Cart-Token' => $cartToken]);

    $this->getJson(H::API.'/me/cart', H::auth($token))->assertOk()->assertJsonPath('data.id', $cartId)->assertJsonPath('data.item_count', 1);
    $this->getJson(H::API."/carts/{$cartId}", H::auth($token))->assertOk();
    $this->getJson(H::API."/carts/{$cartId}", [...H::CHANNEL, 'X-Vani-Cart-Token' => $cartToken])->assertStatus(404);

    $other = H::login($this, '0987654321');
    $this->getJson(H::API."/carts/{$cartId}", H::auth($other))->assertStatus(404);
});

it('đăng nhập trên thiết bị khác kèm giỏ vãng lai → gộp vào giỏ đang có của khách', function () {
    $token = H::login($this);
    $mine = $this->getJson(H::API.'/me/cart', H::auth($token))->json('data.id');
    $this->postJson(H::API."/carts/{$mine}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], H::auth($token))->assertOk();

    [$guestId, $guestToken] = ($this->guestCart)($this->m->id, 2);
    $response = $this->postJson(H::API.'/auth/otp/request', ['phone' => '0912345678'], H::CHANNEL);
    $code = FakeOtpSender::$codes['+84912345678|login'];
    $this->postJson(H::API.'/auth/otp/verify', ['phone' => '0912345678', 'code' => $code, 'cart_id' => $guestId], [...H::CHANNEL, 'X-Vani-Cart-Token' => $guestToken])
        ->assertOk()->assertJsonPath('meta.cart_id', $mine);

    expect($this->getJson(H::API.'/me/cart', H::auth($token))->json('data.item_count'))->toBe(3)
        ->and(DB::table('carts')->where('public_id', $guestId)->value('status'))->toBe('merged');
});

it('khách đăng nhập đặt hàng từ giỏ của mình → đơn gắn khách, thống kê theo brand; huỷ đơn thì tính lại', function () {
    $token = H::login($this);
    $cartId = $this->getJson(H::API.'/me/cart', H::auth($token))->json('data.id');
    $this->postJson(H::API."/carts/{$cartId}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], H::auth($token))->assertOk();

    $number = $this->postJson(H::API."/checkout/{$cartId}/orders", C::orderPayload(['expected_total' => 330_000, 'contact' => ['phone' => '0900000000']]), [...H::auth($token), 'Idempotency-Key' => 'member-order-1'])
        ->assertCreated()->json('data.number');

    $customer = Customer::query()->where('phone', '+84912345678')->sole();
    $order = Order::query()->withoutGlobalScopes()->where('number', $number)->sole();
    expect($order->customer_id)->toBe($customer->id)
        ->and(Customer::query()->where('phone', '+84900000000')->exists())->toBeFalse(); // SĐT người nhận khác không tạo hồ sơ

    $profile = DB::table('customer_brand_profiles')->where('customer_id', $customer->id)->sole();
    expect((int) $profile->orders_count)->toBe(1)->and((int) $profile->total_spent)->toBe(330_000);

    $this->postJson(H::API."/me/orders/{$order->public_id}/cancel", ['reason' => 'đổi ý'], H::auth($token))->assertOk();
    expect(DB::table('customer_brand_profiles')->where('customer_id', $customer->id)->exists())->toBeFalse();
});

it('hai đơn vãng lai cùng SĐT → cùng một profile ẩn', function () {
    H::guestOrder($this, $this->s->id, 'same-phone-1');
    H::guestOrder($this, $this->s->id, 'same-phone-2', ['phone' => '+84 912 345 678']);

    $customer = Customer::query()->sole();
    expect(Order::query()->withoutGlobalScopes()->where('customer_id', $customer->id)->count())->toBe(2)
        ->and((int) DB::table('customer_brand_profiles')->where('customer_id', $customer->id)->value('orders_count'))->toBe(2);
});
