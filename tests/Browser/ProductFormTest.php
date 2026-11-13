<?php

use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

/*
| Form sản phẩm trên trình duyệt thật. Hồi quy 2026-11-13: trang "Thêm sản phẩm" treo — template truyền
| `product?.variants ?? []` (mảng mới mỗi lần render) cho ma trận biến thể → `summary` tính lại → trang render lại, lặp vô hạn.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'pricing.manage', 'inventory.adjust']), 'staff');
});

it('trang Thêm sản phẩm mở được, mở được popup biến thể, lưu tạo sản phẩm', function () {
    $page = visit('/admin/catalog/products/create')->assertSee('Thêm sản phẩm')->assertSee('Chưa có biến thể');

    $page->click('[data-open-variants]')->assertSee('Biến thể — sản phẩm mới')->keys('[role="dialog"]', ['Escape']);
    $page->type('form input >> nth=0', 'Áo sơ mi lụa')
        ->type('form > div:last-child input >> nth=0', 'LM-NEW-01')
        ->type('form > div:last-child input >> nth=1', 'ao-so-mi-lua')
        ->press('Lưu')
        ->assertSee('Sửa: LM-NEW-01')
        ->assertNoJavaScriptErrors();

    expect(T::seed(fn () => Style::query()->where('style_code', 'LM-NEW-01')->exists()))->toBeTrue();
});

it('trang Thêm sản phẩm có ô Ảnh sản phẩm: nút Thêm ảnh mở Thư viện ảnh', function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'media.view', 'media.manage']), 'staff');

    visit('/admin/catalog/products/create')
        ->assertSee('Ảnh sản phẩm')
        ->click('Thêm ảnh')
        ->assertSee('Chọn ảnh từ thư viện')
        ->assertSee('Tải ảnh lên')
        ->assertNoJavaScriptErrors();
});
