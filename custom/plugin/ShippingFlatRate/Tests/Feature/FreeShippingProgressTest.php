<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Tenancy\Contracts\Settings;
use Plugin\ShippingFlatRate\Infrastructure\FreeShippingProgress;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Thanh tiến độ miễn phí giao (1.1.0): cùng ngưỡng free_over với phí giao. Đặc tả: docs/05-plugin/specs/free-shipping-progress.md.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(); // S: 300.000 ₫
    config(['vani.shipping-flat-rate.fee' => 30_000, 'vani.shipping-flat-rate.free_over' => 500_000]);
    $this->set = fn (string $key, mixed $value) => T::seed(fn () => app(Settings::class)->set('vani.shipping-flat-rate', $key, $value));
});

it('tính còn thiếu / phần trăm / đã đạt theo ngưỡng free_over; không ngưỡng hoặc tắt → không có', function () {
    $progress = app(FreeShippingProgress::class);

    expect($progress->forAmount(320_000, 'VND'))->toMatchArray(['percent' => 64, 'achieved' => false, 'message' => 'Hãy thêm 180.000 ₫ để được miễn phí vận chuyển'])
        ->and($progress->forAmount(320_000, 'VND')['remaining']['amount'])->toBe(180_000)
        ->and($progress->forAmount(500_000, 'VND'))->toMatchArray(['percent' => 100, 'achieved' => true])
        ->and($progress->forAmount(0, 'VND'))->toBeNull();

    ($this->set)('progress_enabled', false);
    expect($progress->forAmount(320_000, 'VND'))->toBeNull();
    ($this->set)('progress_enabled', true);

    config(['vani.shipping-flat-rate.free_over' => null]);
    expect($progress->forAmount(320_000, 'VND'))->toBeNull();
});

it('trang giỏ và API giỏ có tiến độ; thanh toán tính theo tiền hàng sau giảm giá, khớp với phí giao', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->get('/gio-hang')->assertOk()->assertSee('Hãy thêm 200.000 ₫ để được miễn phí vận chuyển')->assertSee('aria-valuenow="60"', false);
    $this->get('/thanh-toan')->assertOk()->assertSee('Hãy thêm 200.000 ₫ để được miễn phí vận chuyển');

    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $cart = $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $headers)->assertOk();
    expect($cart->json('data.extensions')['vani.shipping-flat-rate']['free_shipping'])->toMatchArray(['achieved' => true, 'percent' => 100]);

    // Giảm 10% → tiền hàng 540.000 ₫ vẫn ≥ ngưỡng: thanh toán báo đã đạt và phí giao = 0.
    C::promotion(['name' => 'Giảm 10%'], ['GIAM10' => null]);
    $quote = $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/quote", ['voucher_codes' => ['GIAM10'], 'shipping_method' => 'standard'], $headers)->assertOk();
    expect($quote->json('data.shipping_fee.amount'))->toBe(0);
});

it('vị trí theo cấu hình; trang sản phẩm chỉ hiện ngưỡng chung (bật riêng)', function () {
    ($this->set)('progress_on_cart', false);
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->get('/gio-hang')->assertDontSee('data-vani-free-shipping', false);

    $this->get('/san-pham/'.$this->s->style->slug)->assertDontSee('data-vani-free-shipping', false);
    ($this->set)('progress_on_pdp', true);
    $this->get('/san-pham/'.$this->s->style->slug)->assertSee('Miễn phí vận chuyển cho đơn từ 500.000 ₫');
});

it('cấu hình: thông báo phải có {amount}, màu đúng #rrggbb', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'settings.manage'])->create(), 'staff');

    $this->put('/admin/settings', ['namespace' => 'vani.shipping-flat-rate', 'values' => ['progress_message_pending.vi' => 'Mua thêm nhé']])->assertSessionHasErrors('values.progress_message_pending.vi');
    $this->put('/admin/settings', ['namespace' => 'vani.shipping-flat-rate', 'values' => ['progress_color' => 'xanh']])->assertSessionHasErrors('values.progress_color');
    $this->put('/admin/settings', ['namespace' => 'vani.shipping-flat-rate', 'values' => ['progress_message_pending.vi' => 'Còn {amount} là freeship', 'progress_color' => '#fd560f']])->assertSessionHasNoErrors();

    expect(app(FreeShippingProgress::class)->forAmount(400_000, 'VND'))->toMatchArray(['message' => 'Còn 100.000 ₫ là freeship', 'color' => '#fd560f']);
});
