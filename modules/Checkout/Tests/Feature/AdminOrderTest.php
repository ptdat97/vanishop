<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/CheckoutTestHelpers.php';

/*
| Stabilization A8: nhân viên tạo đơn trong Admin — cùng luật checkout (giá, phí giao, giữ hàng), nguồn `admin`, chống tạo trùng.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 5);
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage']), 'staff');
    $address = C::orderPayload()['shipping_address'];
    $this->payload = fn (int $quantity = 2, array $overrides = []): array => [
        'lines' => [['variant_id' => $this->s->id, 'quantity' => $quantity]],
        'full_name' => 'Chị Hoa', 'phone' => '0987 654 321', 'email' => null,
        ...$address, 'note' => 'Khách gọi điện', ...$overrides,
    ];
});

it('tính tiền: tạo giỏ của form, trả tổng, phí giao, phương thức thanh toán; tính lại dùng cùng giỏ', function () {
    $quote = $this->postJson('/admin/orders/new/quote', ($this->payload)(1))->assertOk();
    expect($quote->json('total_amount'))->toBe(330_000)
        ->and($quote->json('shipping_options.0.code'))->toBe('standard')
        ->and(collect($quote->json('payment_methods'))->pluck('code'))->toContain('cod')
        ->and($quote->json('lines.0.unit_price'))->toBe('300.000 ₫');

    $again = $this->postJson('/admin/orders/new/quote', [...($this->payload)(2), 'cart_id' => $quote->json('cart.id'), 'cart_token' => $quote->json('cart.token')])->assertOk();
    expect($again->json('cart.id'))->toBe($quote->json('cart.id'))->and($again->json('total_amount'))->toBe(600_000);
});

it('tạo đơn: nguồn admin, giữ hàng, gắn khách theo SĐT, mở trang đơn; bấm lại cùng khoá không tạo đơn thứ hai', function () {
    $quote = $this->postJson('/admin/orders/new/quote', ($this->payload)())->json();
    $submit = [...($this->payload)(), 'cart_id' => $quote['cart']['id'], 'cart_token' => $quote['cart']['token'], 'shipping_method' => 'standard', 'payment_method' => 'cod',
        'expected_total' => $quote['total_amount'], 'idempotency_key' => '6f1c1d4e-1a2b-4c3d-9e8f-001122334455'];

    $response = $this->post('/admin/orders/new', $submit);
    $order = Order::query()->withoutGlobalScopes()->sole();
    $response->assertRedirect("/admin/orders/orders/{$order->id}")->assertSessionHas('success', 'Đã tạo đơn. Kiểm tra lại rồi xác nhận đơn.');
    expect($order)->source->toBe('admin')->total_amount->toBe(600_000)->customer_phone->toBe('+84987654321')->note->toBe('Khách gọi điện')
        ->and((int) DB::table('stock_levels')->where('variant_id', $this->s->id)->value('reserved'))->toBe(2);

    $this->post('/admin/orders/new', $submit)->assertRedirect("/admin/orders/orders/{$order->id}");
    expect(Order::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('tổng đổi so với lúc tính (giá vừa đổi) → báo, không tạo; hết hàng → báo khi tính tiền', function () {
    $quote = $this->postJson('/admin/orders/new/quote', ($this->payload)())->json();
    $this->post('/admin/orders/new', [...($this->payload)(), 'cart_id' => $quote['cart']['id'], 'cart_token' => $quote['cart']['token'], 'shipping_method' => 'standard',
        'payment_method' => 'cod', 'expected_total' => 1, 'idempotency_key' => '6f1c1d4e-1a2b-4c3d-9e8f-001122334466'])->assertSessionHasErrors('business');
    expect(Order::query()->withoutGlobalScopes()->count())->toBe(0);

    $this->postJson('/admin/orders/new/quote', ($this->payload)(50))->assertStatus(422);
});

it('tìm SKU đang bán; nút Tạo đơn ở danh sách đơn; chỉ xem đơn thì không tạo được', function () {
    $this->getJson('/admin/orders/new/variants?q='.$this->s->sku)->assertOk()->assertJsonPath('data.0.variant_id', $this->s->id);
    $this->postJson('/admin/orders/new/variants', ['skus' => [$this->s->sku, 'SKU-KHONG-CO']])->assertOk()
        ->assertJsonCount(1, 'data')->assertJsonPath('data.0.variant_id', $this->s->id);
    $this->postJson('/admin/orders/new/variants', ['skus' => []])->assertUnprocessable()->assertJsonValidationErrors('skus');
    expect($this->get('/admin/orders/orders')->viewData('page')['props']['createUrl'])->toBe(url('/admin/orders/new'));

    $this->actingAs(T::staff(['admin.access', 'orders.view']), 'staff');
    expect($this->get('/admin/orders/orders')->viewData('page')['props']['createUrl'])->toBeNull();
    $this->get('/admin/orders/new')->assertForbidden();
    $this->postJson('/admin/orders/new/quote', ($this->payload)())->assertForbidden();
    $this->postJson('/admin/orders/new/variants', ['skus' => [$this->s->sku]])->assertForbidden();
});
