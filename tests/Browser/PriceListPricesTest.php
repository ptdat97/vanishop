<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../modules/Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Trang làm việc của bảng giá (thao tác gọn, đợt 1) trên trình duyệt thật.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'pricing.view', 'pricing.manage']), 'staff');
    $this->style = T::product();
    [$this->s, $this->m] = P::variants($this->style);
    P::priceList(['code' => 'base'], [$this->s->id => [299_000], $this->m->id => [299_000]]);
    $this->list = P::priceList(['code' => 'sale-1111', 'type' => 'sale', 'priority' => 10], []);
});

it('bảng giá trống mở được, gợi ý thêm sản phẩm', function () {
    visit("/admin/pricing/price-lists/{$this->list->id}/prices")->assertSee('Bảng giá chưa có sản phẩm')->assertNoJavaScriptErrors();
});

it('thêm sản phẩm → giảm 30% → xem trước → áp dụng: giá vào bảng, trang hiện −30%', function () {
    $page = visit("/admin/pricing/price-lists/{$this->list->id}/prices");

    $page->click('[data-add-products]')
        ->type('input[aria-label="Tìm sản phẩm"]', $this->style->style_code)
        ->click("[data-product=\"{$this->style->style_code}\"]")
        ->click('[data-continue]')
        ->type('input[aria-label="Giảm %"]', '30')
        ->click('[data-preview]')->assertSee('2 SKU đổi giá')
        ->click('[data-apply]')->assertSee('Đã cập nhật giá 2 SKU.')->assertSee('−30%');

    $page->assertNoJavaScriptErrors();
    expect(Price::query()->where('price_list_id', $this->list->id)->pluck('amount')->all())->toBe([209_000, 209_000]);
});

it('đến từ danh sách sản phẩm ("Đưa vào bảng giá…"): hộp thoại cách tính mở sẵn, giảm 20% → áp dụng', function () {
    $page = visit("/admin/pricing/price-lists/{$this->list->id}/prices?add={$this->style->id}");

    $page->assertSee('Đưa 1 sản phẩm vào')
        ->type('input[aria-label="Giảm %"]', '20')
        ->click('[data-preview]')->assertSee('2 SKU đổi giá')
        ->click('[data-apply]')->assertSee('Đã cập nhật giá 2 SKU.');

    $page->assertNoJavaScriptErrors();
    expect(Price::query()->where('price_list_id', $this->list->id)->pluck('amount')->all())->toBe([239_000, 239_000]);
});

it('bảng giá niêm yết: chỉ xem, chỉ đường sửa giá ở sản phẩm', function () {
    $base = PriceList::query()->where('code', 'base')->sole();

    visit("/admin/pricing/price-lists/{$base->id}/prices")->assertSee('chỉ xem ở đây')->assertSee('Sửa giá ở danh sách sản phẩm')->assertDontSee('Thêm sản phẩm')->assertNoJavaScriptErrors();
});

it('nhập giá từ file: chọn tệp, nhập, bảng hiện giá mới; dòng lỗi hiện trong hộp thoại', function () {
    $path = tempnam(sys_get_temp_dir(), 'gia').'.csv';
    file_put_contents($path, "SKU;Giá\n{$this->s->sku};150.000\nKHONG-CO;1000\n");

    $page = visit("/admin/pricing/price-lists/{$this->list->id}/prices");
    $page->click('[data-import]')->attach('input[type="file"]', $path)->click('[data-import-submit]')
        ->assertSee('Dòng 3: không có SKU "KHONG-CO".');

    $page->assertNoJavaScriptErrors();
    expect(Price::query()->where('price_list_id', $this->list->id)->pluck('amount', 'variant_id')->all())->toBe([$this->s->id => 150_000]);
});

it('dán hai dòng giá từ Excel và cập nhật cùng lúc', function () {
    visit("/admin/pricing/price-lists/{$this->list->id}/prices")
        ->click('[data-import]')
        ->fill('[data-paste-prices]', "SKU\tGiá\tGiá gạch\n{$this->s->sku}\t180.000\t220.000\n{$this->m->sku}\t200000\t")
        ->click('[data-import-submit]')
        ->assertValue('input[aria-label="Giá '.$this->s->sku.'"]', '180000')
        ->assertValue('input[aria-label="Giá '.$this->m->sku.'"]', '200000')
        ->assertNoJavaScriptErrors();

    expect(Price::query()->where('price_list_id', $this->list->id)->pluck('amount', 'variant_id')->all())
        ->toMatchArray([$this->s->id => 180_000, $this->m->id => 200_000]);
});

it('SkuPicker: mặc định hiện sản phẩm; mở rộng để chọn từng SKU; lựa chọn giữ khi tìm tiếp', function () {
    $other = T::product();
    P::variants($other);
    $page = visit("/admin/pricing/price-lists/{$this->list->id}/prices");

    $page->click('[data-add-products]')
        ->type('input[aria-label="Tìm sản phẩm"]', $this->style->style_code)
        ->assertSee($this->style->style_code)->assertDontSee($this->s->sku)
        ->click("[data-expand=\"{$this->style->style_code}\"]")->assertSee($this->s->sku)->assertSee($this->m->sku)
        ->click("[data-sku=\"{$this->m->sku}\"]")->assertSee('Đã chọn 1 SKU')
        ->clear('input[aria-label="Tìm sản phẩm"]')->type('input[aria-label="Tìm sản phẩm"]', $other->style_code)
        ->click("[data-product=\"{$other->style_code}\"]")->assertSee('Đã chọn 3 SKU')
        ->click('[data-continue]')
        ->click('label:has-text("Đồng giá")')->type('input[aria-label="Đồng giá"]', '99000')
        ->click('[data-preview]')->assertSee('3 SKU đổi giá')
        ->click('[data-apply]')->assertSee('Đã cập nhật giá 3 SKU.');

    $page->assertNoJavaScriptErrors();
    expect(Price::query()->where('price_list_id', $this->list->id)->pluck('variant_id')->all())->not->toContain($this->s->id)
        ->and(Price::query()->where('price_list_id', $this->list->id)->count())->toBe(3);
});

it('đóng hộp thêm SKU còn thay đổi thì hỏi lại và giữ lựa chọn nếu ở lại', function () {
    $page = visit("/admin/pricing/price-lists/{$this->list->id}/prices")
        ->click('[data-add-products]')
        ->type('input[aria-label="Tìm sản phẩm"]', $this->style->style_code)
        ->click("[data-product=\"{$this->style->style_code}\"]")
        ->keys('[role="dialog"]', 'Escape')
        ->assertSee('Đóng khi còn thay đổi chưa lưu?');

    $page->click('[role="alertdialog"] button[type="button"]')
        ->assertDontSee('Đóng khi còn thay đổi chưa lưu?')
        ->assertScript("!document.querySelector('[data-continue]').disabled", true)
        ->keys('[role="dialog"]', 'Escape')
        ->click('[role="alertdialog"] button[type="submit"]')
        ->assertMissing('[data-continue]')
        ->assertScript("document.activeElement?.matches('[data-add-products]')", true)
        ->assertNoJavaScriptErrors();
});
