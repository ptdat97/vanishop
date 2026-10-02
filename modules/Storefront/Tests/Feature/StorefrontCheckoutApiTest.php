<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Facades\Hook;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['brand' => $this->brand, 's' => $this->s, 'm' => $this->m, 'location' => $this->location] = C::store();
    $this->api = '/api/storefront/v1';
    $this->headers = [];

    $created = $this->postJson("{$this->api}/carts", [], $this->headers)->assertCreated();
    $this->cart = $created->json('data.id');
    $this->cartHeaders = [...$this->headers, 'X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->add = fn ($variant, int $quantity) => $this->postJson("{$this->api}/carts/{$this->cart}/lines", ['variant_id' => $variant->id, 'quantity' => $quantity], $this->cartHeaders)->assertOk();
    $this->quote = fn (array $body = []) => $this->postJson("{$this->api}/checkout/{$this->cart}/quote", $body, $this->cartHeaders);
    $this->place = fn (array $body, string $key = 'order-key-0001') => $this->postJson("{$this->api}/checkout/{$this->cart}/orders", $body, [...$this->cartHeaders, 'Idempotency-Key' => $key]);
});

it('quote: tạm tính, miễn phí giao trên ngưỡng, VAT gồm trong giá, phương thức khả dụng', function () {
    ($this->add)($this->s, 1);
    ($this->add)($this->m, 2);

    ($this->quote)()->assertOk()
        ->assertJsonPath('data.subtotal.amount', 700_000)
        ->assertJsonPath('data.shipping.code', 'standard')
        ->assertJsonPath('data.shipping_fee.amount', 0)
        ->assertJsonPath('data.tax_included.amount', 27_273 + 36_364)
        ->assertJsonPath('data.total.amount', 700_000)
        ->assertJsonPath('data.total.formatted', '700.000 ₫')
        ->assertJsonPath('data.payment_methods.0.code', 'cod')
        ->assertJsonPath('data.cart_ready', true);
});

it('quote: voucher giảm giá, báo voucher không hợp lệ, phí giao dưới ngưỡng', function () {
    C::promotion(['name' => 'Giảm 10%'], ['GIAM10' => 100]);
    ($this->add)($this->s, 1);

    ($this->quote)(['voucher_codes' => ['giam10', 'KHONGCO']])
        ->assertJsonPath('data.discount.amount', 30_000)
        ->assertJsonPath('data.adjustments.0.code', 'GIAM10')
        ->assertJsonPath('data.adjustments.0.amount.amount', -30_000)
        ->assertJsonPath('data.shipping_fee.amount', 30_000)
        ->assertJsonPath('data.total.amount', 300_000)
        ->assertJsonPath('data.rejected_vouchers.0', ['code' => 'KHONGCO', 'reason' => 'not_found']);
});

it('đặt hàng COD thành công: snapshot, số đơn, giữ hàng không hết hạn, đóng giỏ, ghi lượt voucher', function () {
    Event::fake([OrderPlaced::class]);
    C::promotion(['name' => 'Giảm 10%'], ['GIAM10' => 100]);
    ($this->add)($this->s, 1);
    ($this->add)($this->m, 2);

    $response = ($this->place)(C::orderPayload(['voucher_codes' => ['GIAM10'], 'expected_total' => 630_000, 'note' => ' Giao giờ hành chính ']))
        ->assertCreated()
        ->assertJsonPath('data.order_status', 'pending')
        ->assertJsonPath('data.payment_status', 'cod_pending')
        ->assertJsonPath('data.total.amount', 630_000);

    $order = Order::query()->withoutGlobalScopes()->with(['lines', 'adjustments'])->sole();
    expect($response->json('data.number'))->toBe($order->number)
        ->and($order->number)->toMatch('/^VN\d{4}-000001$/')
        ->and($order->customer_snapshot)->toEqual(['full_name' => 'Nguyễn Thị Lan', 'phone' => '+84912345678', 'email' => 'lan@example.com'])
        ->and($order->shipping_address['ward_name'])->toBe('Phường Bến Thành')
        ->and($order->note)->toBe('Giao giờ hành chính')
        ->and([$order->subtotal_amount, $order->discount_amount, $order->shipping_amount, $order->tax_amount, $order->total_amount])->toBe([700_000, 70_000, 0, 24_545 + 32_727, 630_000])
        ->and($order->lines->pluck('total_amount')->all())->toBe([270_000, 360_000])
        ->and($order->lines[0]->product_name)->toBe('Đầm lụa')
        ->and($order->adjustments->sole()->amount)->toBe(-70_000);

    expect(DB::table('stock_reservations')->where('reservation_key', "order:{$order->public_id}")->whereNull('expires_at')->sum('quantity'))->toEqual(3)
        ->and(DB::table('vouchers')->where('code', 'GIAM10')->value('used_count'))->toEqual(1)
        ->and(DB::table('promotion_usages')->where('order_id', $order->id)->value('discount_amount'))->toEqual(70_000)
        ->and(DB::table('order_events')->where('order_id', $order->id)->value('type'))->toBe('placed');
    Event::assertDispatched(OrderPlaced::class, fn (OrderPlaced $event) => $event->orderId === $order->id);

    $this->getJson("{$this->api}/carts/{$this->cart}", $this->cartHeaders)->assertJsonPath('data.status', 'converted');
    $this->postJson("{$this->api}/carts/{$this->cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $this->cartHeaders)
        ->assertStatus(409)->assertJsonPath('error.code', 'cart.closed');
});

it('request trùng Idempotency-Key trả lại đúng kết quả cũ; khác nội dung thì 409', function () {
    ($this->add)($this->s, 1);
    $payload = C::orderPayload(['expected_total' => 330_000]);

    $first = ($this->place)($payload)->assertCreated();
    ($this->place)($payload)->assertCreated()->assertHeader('Idempotent-Replayed', 'true')->assertJson(['data' => $first->json('data')]);
    ($this->place)([...$payload, 'note' => 'khác'])->assertStatus(409)->assertJsonPath('error.code', 'idempotency.conflict');
    ($this->place)($payload, 'order-key-0002')->assertStatus(409)->assertJsonPath('error.code', 'cart.closed');

    expect(Order::query()->withoutGlobalScopes()->count())->toBe(1);
});

it('tổng tiền đổi so với lúc xem → 409 kèm tổng mới, không tạo đơn', function () {
    ($this->add)($this->s, 1);
    P::priceList(['code' => 'sale', 'type' => 'sale', 'priority' => 10], [$this->s->id => [250_000]]);

    ($this->place)(C::orderPayload(['expected_total' => 330_000]))
        ->assertStatus(409)
        ->assertJsonPath('error.code', 'checkout.totals_changed')
        ->assertJsonPath('error.details.total', 280_000);

    expect(Order::query()->withoutGlobalScopes()->count())->toBe(0)
        ->and(DB::table('idempotency_keys')->count())->toBe(0);
    ($this->place)(C::orderPayload(['expected_total' => 280_000]))->assertCreated();
});

it('giỏ có dòng thiếu hàng → 422 cart_not_ready, không tạo đơn (tranh chấp lúc giữ hàng: xem concurrency test)', function () {
    ($this->add)($this->s, 2);
    I::stock($this->location, $this->s->id, 1);

    ($this->place)(C::orderPayload(['expected_total' => 630_000]))->assertStatus(422)->assertJsonPath('error.details.issues.0.code', 'cart_not_ready');

    I::stock($this->location, $this->s->id, 2);
    DB::table('stock_levels')->where('variant_id', $this->s->id)->update(['reserved' => 1]);
    ($this->place)(C::orderPayload(['expected_total' => 600_000]))->assertStatus(422);

    expect(Order::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('voucher hết lượt lúc đặt → 409 promotion.voucher_exhausted', function () {
    C::promotion([], ['CUOI' => 1]);
    ($this->add)($this->s, 1);
    $quote = ($this->quote)(['voucher_codes' => ['CUOI']])->json('data.total.amount');

    // Người khác vừa dùng lượt cuối sau khi khách xem tổng.
    DB::table('vouchers')->where('code', 'CUOI')->update(['used_count' => 1]);

    ($this->place)(C::orderPayload(['voucher_codes' => ['CUOI'], 'expected_total' => $quote]))
        ->assertStatus(422)
        ->assertJsonPath('error.code', 'checkout.voucher_invalid')
        ->assertJsonPath('error.details.vouchers.0.reason', 'exhausted');
    expect(Order::query()->withoutGlobalScopes()->count())->toBe(0);
});

it('validate liên hệ, địa chỉ, thanh toán; plugin chặn qua hook', function () {
    ($this->add)($this->s, 1);

    $response = ($this->place)(C::orderPayload(['contact' => ['phone' => '123'], 'shipping_address' => ['ward_code' => ''], 'payment_method' => 'bitcoin', 'expected_total' => 330_000]))
        ->assertStatus(422)->assertJsonPath('error.code', 'checkout.invalid');
    expect(collect($response->json('error.details.issues'))->pluck('field')->all())->toContain('contact.phone', 'shipping_address.ward_code', 'payment_method');

    Hook::onValidate('vani.checkout.after_validate', fn ($request, $totals) => $totals->grandTotal->amount < 500_000 ? ['Đơn COD tối thiểu 500.000 ₫.'] : []);
    ($this->place)(C::orderPayload(['expected_total' => 330_000]), 'order-key-0003')
        ->assertStatus(422)->assertJsonPath('error.message', 'Đơn COD tối thiểu 500.000 ₫.');
});

it('bắt buộc Idempotency-Key và expected_total', function () {
    ($this->add)($this->s, 1);

    $this->postJson("{$this->api}/checkout/{$this->cart}/orders", C::orderPayload(['expected_total' => 330_000]), $this->cartHeaders)
        ->assertStatus(400)->assertJsonPath('error.code', 'http.400');
    ($this->place)(C::orderPayload())->assertStatus(422)->assertJsonPath('error.code', 'validation.failed');
});
