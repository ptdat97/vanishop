<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Stabilization A8: tạo đơn trong Admin trên trình duyệt thật — tìm SKU, tính tiền tự động, chọn địa chỉ, tạo đơn.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 5);
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage']), 'staff');
});

it('thêm SKU từ ô tìm, tổng tự tính; điền người nhận + địa chỉ → Tạo đơn mở trang đơn nguồn admin', function () {
    $page = visit('/admin/orders/new');

    $page->type('[data-add-variant] input', $this->s->sku)
        ->click('[role="option"]')
        ->assertSee($this->s->sku)
        ->assertSee('330.000 ₫')
        ->type('input[maxlength="120"]', 'Chị Hoa')
        ->type('input[type="tel"]', '0987654321')
        ->type('input[aria-label="Tỉnh/thành"]', 'Hồ Chí Minh')->click('[role="option"]')
        ->type('input[aria-label="Phường/xã"]', 'Bến Thành')->click('[role="option"]')
        ->type('input[maxlength="255"]', '12 Lê Lợi')
        ->click('[data-quote]')
        ->click('[data-place]')
        ->assertSee('Đã tạo đơn');

    $page->assertNoJavaScriptErrors();
    expect(Order::query()->withoutGlobalScopes()->sole())->source->toBe('admin')->customer_phone->toBe('+84987654321');
});

it('Combobox tìm SKU phía máy chủ đổi từ kết quả trống sang lựa chọn được bằng bàn phím', function () {
    $search = '[data-add-variant] input';
    visit('/admin/orders/new')
        ->type($search, 'KHONG-CO-SKU')
        ->assertSee('Không tìm thấy SKU đang bán')
        ->clear($search)
        ->type($search, $this->s->sku)
        ->assertSeeIn('[role="option"]', $this->s->sku)
        ->keys($search, ['ArrowDown', 'Enter'])
        ->assertSeeIn('[data-order-lines]', $this->s->sku)
        ->assertNoJavaScriptErrors();
});
