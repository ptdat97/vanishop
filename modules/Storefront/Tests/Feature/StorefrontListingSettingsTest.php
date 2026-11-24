<?php

use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Tenancy\Contracts\Settings;

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    foreach (range(1, 14) as $index) {
        [$variant] = P::variants(T::product($this->brand->id, ['name' => "Đầm {$index}"]), ['S']);
        P::priceList(['code' => "base-{$index}"], [$variant->id => [100_000]]);
    }
    $this->set = fn (string $key, mixed $value) => T::seed(fn () => app(Settings::class)->set('core', $key, $value));
});

it('lưu số sản phẩm mỗi trang từ Admin và áp dụng cho web lẫn Storefront API', function () {
    $this->getJson('/api/storefront/v1/products')->assertOk()->assertJsonPath('meta.per_page', 24);
    $this->actingAs(T::staff(['admin.access', 'settings.store']), 'staff');
    $settingsUrl = route('admin.settings.system', 'store');

    $this->from($settingsUrl)->put(route('admin.settings.update'), [
        'namespace' => 'core',
        'values' => ['storefront.per_page' => '12'],
    ])->assertRedirect($settingsUrl)->assertSessionHasNoErrors();

    expect(app(Settings::class)->get('core', 'storefront.per_page'))->toBe(12);
    $this->getJson('/api/storefront/v1/products')->assertOk()->assertJsonPath('meta.per_page', 12)->assertJsonCount(12, 'data');
    $this->getJson('/api/storefront/v1/products?per_page=')->assertOk()->assertJsonPath('meta.per_page', 12);
    $this->getJson('/api/storefront/v1/products?per_page=5')->assertJsonPath('meta.per_page', 5);

    $firstPage = $this->get('/thuong-hieu/lumiere')->assertOk()->assertSee('data-vani-pagination', false)->assertSee('?page=2', false);
    $secondPage = $this->get('/thuong-hieu/lumiere?page=2')->assertOk()->assertSee('aria-current="page"', false);
    $productCard = '<article class="group vani-product-card"';
    expect(substr_count($firstPage->getContent(), $productCard))->toBe(12);
    expect(substr_count($secondPage->getContent(), $productCard))->toBe(2);
});

it('Admin chỉ nhận per_page 12–60', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'settings.manage'])->create(), 'staff');

    $this->put(route('admin.settings.update'), [
        'namespace' => 'core',
        'values' => ['storefront.per_page' => 60],
    ])->assertSessionHasNoErrors();

    foreach ([11, 61] as $invalidSize) {
        $this->put(route('admin.settings.update'), [
            'namespace' => 'core',
            'values' => ['storefront.per_page' => $invalidSize],
        ])->assertSessionHasErrors('values.storefront.per_page');
    }

    expect(app(Settings::class)->get('core', 'storefront.per_page'))->toBe(60);

    $this->put(route('admin.settings.update'), [
        'namespace' => 'core',
        'values' => ['storefront.per_page' => ''],
    ])->assertSessionHasNoErrors();

    expect(app(Settings::class)->get('core', 'storefront.per_page'))->toBe(24);
});

it('storefront.popular_searches: gợi ý ở ô tìm kiếm (datalist) và trang tìm kiếm khi chưa gõ; bỏ dòng trống, trùng', function () {
    $this->get('/tim-kiem')->assertOk()->assertDontSee('data-vani-popular-searches', false)->assertDontSee('vani-popular-searches', false);

    ($this->set)('storefront.popular_searches', "đầm lụa\n\n  áo linen \nđầm lụa");
    $this->get('/')->assertOk()->assertSeeInOrder(['<datalist', '<option value="đầm lụa"></option>', '<option value="áo linen"></option>', '</datalist>'], false);
    $this->get('/tim-kiem')->assertOk()->assertSee('data-vani-popular-searches', false)->assertSee(url('/tim-kiem?q=').'%C4%91%E1%BA%A7m%20l%E1%BB%A5a', false);
    $this->get('/tim-kiem?q=dam')->assertOk()->assertDontSee('data-vani-popular-searches', false);
});
