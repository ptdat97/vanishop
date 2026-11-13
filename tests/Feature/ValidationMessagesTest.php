<?php

use Illuminate\Support\Facades\Validator;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

/*
| Thông báo validation tiếng Việt (lang/vi/validation.php) và tên trường dễ đọc cho khoá lồng nhau (LocalizedValidator).
*/

it('form Admin báo lỗi tiếng Việt với tên trường dễ đọc, kể cả trường theo ngôn ngữ', function () {
    $this->actingAs(T::staff(), 'staff');

    $this->post('/admin/catalog/products', ['translations' => ['vi' => ['name' => '']]])
        ->assertSessionHasErrors([
            'slug' => 'Đường dẫn (slug) không được để trống.',
            'style_code' => 'Mã sản phẩm không được để trống.',
            'translations.vi.name' => 'Tên (Tiếng Việt) không được để trống.',
        ]);
});

it('khoá lồng nhau: dòng đánh số từ 1, ngôn ngữ theo nhãn cửa hàng; tên truyền riêng được giữ', function () {
    $errors = Validator::make(
        ['cells' => [['sku' => ''], ['sku' => '']], 'translations' => ['en' => ['meta_title' => str_repeat('x', 300)]], 'images' => ['x'], 'email' => 'x'],
        ['cells.*.sku' => 'required', 'translations.en.meta_title' => 'max:255', 'images.*' => 'integer', 'email' => 'email', 'lines.*.quantity' => 'required'],
        [],
        ['email' => 'email liên hệ'],
    )->errors();

    expect($errors->first('cells.1.sku'))->toBe('SKU (dòng 2) không được để trống.')
        ->and($errors->first('translations.en.meta_title'))->toBe('Meta title (English) không được dài quá 255 ký tự.')
        ->and($errors->first('images.0'))->toBe('Ảnh (dòng 1) phải là số nguyên.')
        ->and($errors->first('email'))->toBe('Email liên hệ phải là địa chỉ email hợp lệ.');
});
