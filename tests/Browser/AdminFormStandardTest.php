<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../modules/Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Admin Form Standard trên trình duyệt thật (useAdminForm, useLeaveGuard, Toaster) — form sửa bảng giá làm mẫu.
*/

const NAME_INPUT = 'form > div:first-child input';

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'pricing.view', 'pricing.manage']), 'staff');
    $this->list = P::priceList(['code' => 'sale-1111', 'type' => 'sale'], []);
    $this->edit = "/admin/pricing/price-lists/{$this->list->id}/edit";
});

it('lưu liên tiếp: mỗi lần hiện toast thành công, lần hai không báo nhầm xung đột, phiên bản tăng đúng', function () {
    $page = visit($this->edit);

    $page->type(NAME_INPUT, 'Sale 11.11')->press('Lưu')->assertSee('Đã lưu.');
    $page->type(NAME_INPUT, 'Sale 11.11 lần hai')->press('Lưu')->assertSee('Sửa bảng giá: Sale 11.11 lần hai');

    $page->assertDontSee('Bảng giá đã được người khác cập nhật')->assertNoJavaScriptErrors();
    expect($this->list->fresh())->name->toBe('Sale 11.11 lần hai')->lock_version->toBe(2);
});

it('người khác lưu trước → toast lỗi xung đột, không ghi đè', function () {
    $page = visit($this->edit)->assertValue(NAME_INPUT, 'Bảng giá'); // trang đã tải (visit chỉ mở khi có thao tác đầu tiên)
    T::seed(fn () => DB::table('price_lists')->where('id', $this->list->id)->update(['name' => 'Người khác sửa', 'lock_version' => 5]));

    $page->type(NAME_INPUT, 'Ghi đè')->press('Lưu')->assertSee('Bảng giá đã được người khác cập nhật. Vui lòng tải lại trang.');

    $page->assertNoJavaScriptErrors();
    expect($this->list->fresh()->name)->toBe('Người khác sửa');
});

it('lỗi validation: báo tại ô và một toast nhắc; sửa lại thì lưu được', function () {
    $page = visit($this->edit);

    $page->clear(NAME_INPUT)->press('Lưu')->assertSee('Chưa lưu được: kiểm tra các ô báo lỗi.');
    $page->type(NAME_INPUT, 'Có tên')->press('Lưu')->assertSee('Đã lưu.')->assertNoJavaScriptErrors();
    expect($this->list->fresh()->name)->toBe('Có tên');
});

it('rời trang khi còn thay đổi chưa lưu → hỏi lại; "Thôi" ở lại, "Rời trang" thì đi; đã lưu thì không hỏi', function () {
    $page = visit($this->edit);

    $page->type(NAME_INPUT, 'Chưa lưu')->click('Quay lại')
        ->assertSee('Rời trang khi còn thay đổi chưa lưu?')
        ->click('Thôi')
        ->assertPathIs($this->edit)
        ->assertValue(NAME_INPUT, 'Chưa lưu');

    $page->click('Quay lại')->click('Rời trang')->assertPathIs('/admin/pricing/price-lists');
    expect($this->list->fresh()->name)->toBe('Bảng giá');

    $saved = visit($this->edit);
    $saved->type(NAME_INPUT, 'Đã lưu rồi')->press('Lưu')->assertSee('Đã lưu.')
        ->click('Quay lại')->assertPathIs('/admin/pricing/price-lists')->assertDontSee('Rời trang khi còn thay đổi chưa lưu?')
        ->assertNoJavaScriptErrors();
});
