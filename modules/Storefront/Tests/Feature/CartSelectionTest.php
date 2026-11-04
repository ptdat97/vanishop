<?php

use Modules\Cart\Persistence\Models\Cart;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s, 'm' => $this->m] = C::store();
    $this->api = '/api/storefront/v1';

    $created = $this->postJson("{$this->api}/carts")->assertCreated();
    $this->cart = $created->json('data.id');
    $this->cartHeaders = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->add = fn ($variant, int $quantity, array $extra = []) => $this->postJson("{$this->api}/carts/{$this->cart}/lines", ['variant_id' => $variant->id, 'quantity' => $quantity, ...$extra], $this->cartHeaders)->assertOk();
    $this->select = fn (array $body) => $this->putJson("{$this->api}/carts/{$this->cart}/selection", $body, $this->cartHeaders);
    $this->place = fn (array $body, string $key) => $this->postJson("{$this->api}/checkout/{$this->cart}/orders", $body, [...$this->cartHeaders, 'Idempotency-Key' => $key]);
});

it('API: chọn dòng — mặc định đã chọn, chỉ chọn một phần, bỏ chọn hết thì không thanh toán được, id lạ bị từ chối', function () {
    ($this->add)($this->s, 1);
    $lines = ($this->add)($this->m, 2)->assertJsonPath('data.lines.1.selected', true)->json('data.lines');

    ($this->select)(['line_ids' => [$lines[0]['id']]])->assertOk()
        ->assertJsonPath('data.lines.0.selected', true)
        ->assertJsonPath('data.lines.1.selected', false)
        ->assertJsonPath('data.subtotal.amount', 700_000)
        ->assertJsonPath('data.selected_subtotal.amount', 300_000)
        ->assertJsonPath('data.selected_count', 1)
        ->assertJsonPath('data.checkout_ready', true);

    ($this->select)(['mode' => 'deselect', 'line_ids' => [$lines[0]['id']]])->assertOk()
        ->assertJsonPath('data.selected_count', 0)
        ->assertJsonPath('data.checkout_ready', false);
    ($this->select)(['mode' => 'select', 'line_ids' => [$lines[1]['id']]])->assertOk()
        ->assertJsonPath('data.lines.1.selected', true);

    ($this->select)(['line_ids' => [999_999]])->assertNotFound()->assertJsonPath('error.code', 'cart.line_not_found');
    ($this->select)(['mode' => 'all', 'line_ids' => []])->assertUnprocessable();
});

it('đặt hàng chỉ lấy dòng đã chọn: khuyến mãi tính trên phần đó, dòng chưa chọn ở lại giỏ, giỏ chỉ đóng khi hết dòng', function () {
    C::promotion(['name' => 'Giảm 10%'], ['GIAM10' => 100]);
    $first = ($this->add)($this->s, 1)->json('data.lines.0.id');
    ($this->add)($this->m, 2);
    ($this->select)(['line_ids' => [$first]])->assertOk();

    $this->postJson("{$this->api}/checkout/{$this->cart}/quote", ['voucher_codes' => ['GIAM10']], $this->cartHeaders)
        ->assertJsonPath('data.subtotal.amount', 300_000)
        ->assertJsonPath('data.discount.amount', 30_000);
    ($this->place)(C::orderPayload(['voucher_codes' => ['GIAM10'], 'expected_total' => 300_000]), 'selection-0001')->assertCreated();

    $order = Order::query()->withoutGlobalScopes()->with('lines')->sole();
    expect($order->lines->pluck('variant_id')->all())->toBe([$this->s->id])
        ->and($order->subtotal_amount)->toBe(300_000);

    $this->getJson("{$this->api}/carts/{$this->cart}", $this->cartHeaders)->assertOk()
        ->assertJsonPath('data.status', 'active')
        ->assertJsonCount(1, 'data.lines')
        ->assertJsonPath('data.lines.0.variant_id', $this->m->id)
        ->assertJsonPath('data.lines.0.selected', false)
        ->assertJsonPath('data.checkout_ready', false);

    // Lần đặt thứ hai lấy nốt dòng còn lại → giỏ hết dòng → đóng.
    ($this->select)(['mode' => 'select', 'line_ids' => [$this->getJson("{$this->api}/carts/{$this->cart}", $this->cartHeaders)->json('data.lines.0.id')]])->assertOk();
    ($this->place)(C::orderPayload(['expected_total' => 430_000]), 'selection-0002')->assertCreated();
    expect(Cart::query()->where('public_id', $this->cart)->value('status')->value)->toBe('converted');
});

it('mua ngay: chỉ chọn dòng vừa thêm, dòng khác ở lại giỏ; thêm lại dòng đã bỏ chọn thì chọn lại', function () {
    ($this->add)($this->s, 1);

    ($this->add)($this->m, 1, ['buy_now' => true])
        ->assertJsonPath('data.lines.0.selected', false)
        ->assertJsonPath('data.lines.1.selected', true)
        ->assertJsonPath('data.selected_subtotal.amount', 200_000);

    ($this->add)($this->s, 1)->assertJsonPath('data.lines.0.selected', true)->assertJsonPath('data.lines.0.quantity', 2);
});

it('web: nút "Mua ngay" chuyển tới checkout, form chọn dòng hoạt động khi tắt JS, phiên trả giỏ mini', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1])->assertRedirect('/gio-hang');
    $this->post('/gio-hang', ['variant_id' => $this->m->id, 'quantity' => 2, 'buy_now' => '1'])->assertRedirect('/thanh-toan');

    $this->get('/thanh-toan')->assertOk()->assertSee('400.000');
    $this->get('/gio-hang')->assertOk()->assertSee(__('Chọn tất cả'))->assertSee('name="lines[]"', false);

    $mini = $this->getJson('/_vani/phien')->assertOk()->json('cart');
    expect($mini['count'])->toBe(3)
        ->and($mini['subtotal']['amount'])->toBe(700_000)
        ->and(array_column($mini['lines'], 'quantity'))->toBe([2, 1]);

    $lineIds = array_column($mini['lines'], 'id');
    $this->post('/gio-hang/chon', ['lines' => $lineIds])->assertRedirect('/gio-hang');
    $this->get('/gio-hang')->assertSee('700.000');
    $this->post('/gio-hang/chon', [])->assertRedirect('/gio-hang');
    $this->get('/gio-hang')->assertSee(__('Chọn ít nhất một sản phẩm để thanh toán.'));
});
