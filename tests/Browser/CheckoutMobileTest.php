<?php

use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Persistence\Models\CustomerAddress;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../modules/Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| Checkout trên điện thoại (stabilization S2): thanh tổng + "Đặt hàng" ở đáy màn hình, tìm phường/xã gõ không dấu, tính
| lại khi đổi phường, tóm tắt lỗi nhận focus, chọn địa chỉ đã lưu.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->buyNow = fn () => visit("/san-pham/{$this->s->style->slug}?sku={$this->s->sku}")->on()->mobile()
        ->click('Mua ngay')
        ->assertPathIs('/thanh-toan');
});

it('khách vãng lai đặt COD trên điện thoại: tìm phường không dấu, bấm "Đặt hàng" ở thanh đáy', function () {
    $page = ($this->buyNow)();
    $page->assertVisible('[data-checkout-bar]')
        ->assertMissing('[data-checkout-summary] button[type="submit"]:not([name])')
        ->assertSeeIn('[data-checkout-bar]', '330.000');

    $page->script("() => { document.querySelector('[data-checkout-summary]').dataset.stale = '1'; }");
    $page->fill('#contact\\.full_name', 'Nguyễn Thị Lan')
        ->fill('#contact\\.phone', '0912345678')
        ->select('#province_code', '29')
        ->assertVisible('[data-ward-filter]')
        ->type('[data-ward-filter]', 'phuong ben thanh')
        ->assertValue('#ward_code', '70101065')
        ->fill('#shipping_address\\.street_line', '12 Lê Lợi')
        ->wait(0.5)
        // Chọn phường → tính lại: khung tóm tắt là bản server vừa render (không còn đánh dấu cũ).
        ->assertScript("document.querySelector('[data-checkout-summary]').dataset.stale === undefined", true)
        ->click('[data-checkout-bar] button[type="submit"]')
        ->assertPathBeginsWith('/don-hang/')
        ->assertNoJavaScriptErrors();

    expect(Order::query()->count())->toBe(1)
        ->and(Order::query()->value('shipping_address'))->toMatchArray(['ward_code' => '70101065']);
});

it('đặt hàng thiếu thông tin: tóm tắt lỗi đầu trang nhận focus, link nhảy tới ô lỗi', function () {
    $page = ($this->buyNow)();
    $page->click('[data-checkout-bar] button[type="submit"]')
        ->assertPathIs('/thanh-toan')
        ->assertVisible('[data-checkout-errors]')
        ->assertScript("document.activeElement?.id === 'checkout-errors'", true)
        ->assertNoJavaScriptErrors();

    expect(Order::query()->count())->toBe(0);
});

it('khách đăng nhập chọn địa chỉ đã lưu: điền người nhận, tỉnh, phường', function () {
    $customer = H::account('0912345678');
    foreach ([['Nhà', '01', 'Thành phố Hà Nội', '10101003', 'Phường Ba Đình', true], ['Công ty', '29', 'Thành phố Hồ Chí Minh', '70101065', 'Phường Bến Thành', false]] as [$label, $province, $provinceName, $ward, $wardName, $default]) {
        CustomerAddress::query()->create([
            'customer_id' => $customer->id, 'label' => $label, 'full_name' => "Người nhận {$label}", 'phone' => '+84987654321',
            'province_code' => $province, 'province_name' => $provinceName, 'ward_code' => $ward, 'ward_name' => $wardName,
            'street_line' => "Số 1 {$label}", 'is_default' => $default,
        ]);
    }
    // Cùng một trang trình duyệt (cùng phiên): đăng nhập rồi mua ngay.
    $page = visit('/tai-khoan/dang-nhap')->on()->mobile()
        ->fill('email', $customer->email)->fill('password', H::PASSWORD)->click('form[data-vani-login] button[type="submit"]')->wait(1)
        ->assertPathIs('/tai-khoan')
        ->navigate("/san-pham/{$this->s->style->slug}?sku={$this->s->sku}")
        ->click('Mua ngay')
        ->assertPathIs('/thanh-toan');
    $page->assertValue('#contact\\.full_name', 'Người nhận Nhà')
        ->assertValue('#ward_code', '10101003')
        ->select('[data-checkout-saved-address]', (string) CustomerAddress::query()->where('label', 'Công ty')->value('id'))
        ->wait(1)
        ->assertValue('#contact\\.full_name', 'Người nhận Công ty')
        ->assertValue('#shipping_address\\.street_line', 'Số 1 Công ty')
        ->assertValue('#province_code', '29')
        ->assertValue('#ward_code', '70101065')
        ->assertNoJavaScriptErrors();
});
