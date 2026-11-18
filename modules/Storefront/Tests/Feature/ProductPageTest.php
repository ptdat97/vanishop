<?php

use Modules\Storefront\View\ProductPage;

/*
| Dữ liệu hiển thị trang sản phẩm dựng sẵn ở Core (`$page` của `theme::pages.product`): metadata có dự phòng, ảnh phóng to,
| CSS ẩn/hiện nhóm SKU thoát ký tự; template của theme chỉ in ra, không gọi lớp Core.
*/

beforeEach(function () {
    $this->product = fn (array $overrides = []): array => [
        'id' => 1, 'name' => 'Đầm lụa', 'style_code' => 'DAM01', 'description' => "Lụa <b>mềm</b>,\n\nmặc mát.", 'meta_title' => null, 'meta_description' => null,
        'image_url' => '/anh/goc.jpg', 'brand' => null, 'price' => null, 'in_stock' => true, 'breadcrumb' => [['name' => 'Nữ', 'slug' => 'nu']],
        'media' => ['gallery' => [
            ['id' => 1, 'url' => '/anh/1-600.jpg', 'srcset' => '/anh/1-300.jpg 300w, /anh/1-1200.jpg 1200w, /anh/1-600.jpg 600w', 'thumb_url' => '/anh/1-160.jpg', 'alt' => null],
            ['id' => 2, 'url' => '/anh/2.jpg', 'srcset' => null, 'thumb_url' => null, 'alt' => 'Mặt sau'],
        ]],
        'options' => [], 'variants' => [['id' => 9, 'sku' => 'DAM01-S', 'is_active' => true, 'is_default' => true]], 'purchase' => ['is_purchasable' => true],
        ...$overrides,
    ];
});

it('metadata: meta_title/meta_description, thiếu thì dùng tên và đoạn đầu mô tả (bỏ thẻ); JSON-LD dùng ảnh gallery đầu', function () {
    $meta = app(ProductPage::class)->for(($this->product)())['meta'];
    expect($meta['title'])->toBe('Đầm lụa')
        ->and($meta['description'])->toBe('Lụa mềm, mặc mát.')
        ->and(json_decode($meta['json_ld'][0], true))->toMatchArray(['@type' => 'Product', 'sku' => 'DAM01', 'image' => '/anh/1-600.jpg']);

    $meta = app(ProductPage::class)->for(($this->product)(['meta_title' => 'Đầm lụa cao cấp', 'meta_description' => ' Mô tả SEO ', 'description' => null]))['meta'];
    expect([$meta['title'], $meta['description']])->toBe(['Đầm lụa cao cấp', 'Mô tả SEO'])
        ->and(app(ProductPage::class)->for(($this->product)(['description' => '  ']))['meta']['description'])->toBeNull();
});

it('gallery: ảnh phóng to là bản lớn nhất trong srcset (cao theo tỉ lệ khung), không srcset → ảnh chính 800px; không ảnh → ảnh đại diện', function () {
    [$ratioW, $ratioH] = vani_product_ratio();
    $gallery = app(ProductPage::class)->for(($this->product)())['gallery'];

    expect($gallery['slides'][0]['zoom'])->toBe(['url' => '/anh/1-1200.jpg', 'width' => 1200, 'height' => (int) round(1200 * $ratioH / $ratioW)])
        ->and($gallery['slides'][0]['main'])->toBeTrue()
        ->and($gallery['slides'][1]['zoom']['url'])->toBe('/anh/2.jpg')
        ->and($gallery['slides'][1]['zoom']['width'])->toBe(800)
        ->and($gallery['slides'][1]['alt'])->toBe('Mặt sau')
        ->and(array_column($gallery['thumbs'], 'thumb_url'))->toBe(['/anh/1-160.jpg', '/anh/2.jpg'])
        ->and($gallery['navigation'])->toBeTrue();

    $single = app(ProductPage::class)->for(($this->product)(['media' => ['gallery' => []]]))['gallery'];
    expect(array_column($single['slides'], 'url'))->toBe(['/anh/goc.jpg'])
        ->and($single['thumbs'])->toBe([])
        ->and($single['navigation'])->toBeFalse();
});

it('CSS bộ chọn: mã giá trị được thoát — không thoát khỏi chuỗi CSS hay thẻ <style>', function () {
    $product = ($this->product)([
        'options' => [['code' => 'color', 'name' => 'Màu', 'display_type' => 'swatch', 'values' => [['id' => 5, 'code' => 'x"]</style><script>', 'name' => 'X']]]],
        'variants' => [['id' => 9, 'sku' => 'A', 'is_active' => true, 'is_default' => true, 'availability' => 'in_stock', 'price' => null, 'option_values' => [['option' => 'color', 'value_id' => 5]]]],
        'price_hidden' => true,
    ]);
    $css = app(ProductPage::class)->for($product)['variant_css'];

    expect($css)->not->toContain('<')->not->toContain('x"')
        ->and($css)->toContain('[data-option-value="x\22 \5d \3c \2f style\3e \3c script\3e "]');
});

it('template trang sản phẩm và breadcrumb của vani-base không gọi lớp Core (StructuredData, VariantSelector…)', function () {
    foreach (['pages/product.blade.php', 'partials/breadcrumb.blade.php'] as $file) {
        expect((string) file_get_contents(base_path("custom/theme/vani-base/views/{$file}")))->not->toContain('\\Modules\\')->not->toContain('vani_product_ratio');
    }
});
