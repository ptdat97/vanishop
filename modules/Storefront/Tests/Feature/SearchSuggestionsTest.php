<?php

use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'dam-xinh', 'name' => 'Đầm Xinh']);
    Brand::factory()->create(['slug' => 'an-di', 'name' => 'Đầm Ẩn', 'status' => Brand::HIDDEN]);
    T::seed(function () {
        $category = Category::factory()->create(['slug' => 'dam-vay']);
        $category->syncTranslations(['vi' => ['name' => 'Đầm váy'], 'en' => ['name' => 'Dresses', 'slug' => 'dresses']]);
        $hidden = Category::factory()->hidden()->create(['slug' => 'dam-an']);
        $hidden->syncTranslations(['vi' => ['name' => 'Đầm ẩn']]);
    });
    [$variant] = P::variants(T::product($this->brand->id, ['name' => 'Đầm lụa đen', 'slug' => 'dam-lua-den']), ['S']);
    P::priceList(['code' => 'base'], [$variant->id => [300_000]]);
    T::product($this->brand->id, ['name' => 'Đầm nháp', 'slug' => 'dam-nhap', 'status' => 'draft']);
});

it('gợi ý: sản phẩm, danh mục, thương hiệu đang hiện — không phân biệt dấu/hoa thường, kèm URL theo ngôn ngữ, không có giá', function () {
    $data = $this->getJson('/api/storefront/v1/search/suggestions?q=DAM')->assertOk()->json('data');

    expect(array_column($data['products'], 'slug'))->toBe(['dam-lua-den'])
        ->and($data['products'][0])->not->toHaveKey('price')
        ->and($data['products'][0]['url'])->toBe(url('/san-pham/dam-lua-den'))
        ->and(array_column($data['categories'], 'name'))->toBe(['Đầm váy'])
        ->and($data['categories'][0]['url'])->toBe(url('/danh-muc/dam-vay'))
        ->and(array_column($data['brands'], 'slug'))->toBe(['dam-xinh'])
        ->and($data['search_url'])->toBe(url('/tim-kiem?q=DAM'));

    $en = $this->getJson('/api/storefront/v1/search/suggestions?q=dress', ['X-Vani-Locale' => 'en'])->json('data');
    expect($en['categories'][0])->toMatchArray(['name' => 'Dresses', 'url' => url('/en/category/dresses')]);
});

it('dưới 2 ký tự → rỗng; thiếu q → 422', function () {
    $this->getJson('/api/storefront/v1/search/suggestions?q=d')->assertOk()->assertExactJson(['data' => ['products' => [], 'categories' => [], 'brands' => [], 'search_url' => url('/tim-kiem?q=d')]]);
    $this->getJson('/api/storefront/v1/search/suggestions')->assertUnprocessable();
});

it('ô tìm kiếm của theme khai báo endpoint gợi ý', function () {
    $this->get('/')->assertOk()->assertSee('data-vani-suggest="'.route('api.storefront.v1.search.suggestions').'"', false)->assertSee('data-vani-suggest-box', false);
});
