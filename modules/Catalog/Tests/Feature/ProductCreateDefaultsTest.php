<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/CatalogTestHelpers.php';

/*
| Thêm sản phẩm bớt nhập tay: mã gợi ý (mã kế tiếp của mã gần nhất), mã tự viết hoa, slug trống → theo tên không trùng,
| slug gõ có dấu → chuẩn hoá, một danh mục → danh mục chính.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'settings.manage']), 'staff');
    $this->category = T::seed(fn () => Category::factory()->create());
    $this->create = fn (array $overrides = []) => $this->post('/admin/catalog/products', array_replace(
        ['product_code' => '', 'slug' => '', 'status' => 'draft', 'category_ids' => [], 'translations' => ['vi' => ['name' => 'Áo sơ mi Linen']]],
        $overrides,
    ));
    $this->latest = fn (): Product => T::seed(fn () => Product::query()->latest('id')->firstOrFail());
});

it('mã gợi ý: chưa có sản phẩm → SP0001; sau đó tăng số cuối của mã gần nhất, giữ độ dài, bỏ mã đã có', function () {
    $this->get('/admin/catalog/products/create')->assertInertia(fn (Assert $page) => $page->where('suggestedProductCode', 'SP0001'));
    ($this->create)()->assertSessionHasNoErrors();
    expect(($this->latest)()->product_code)->toBe('SP0001');

    T::product(null, ['product_code' => 'AO013']); // đã có từ trước
    ($this->create)(['product_code' => 'ao012'])->assertSessionHasNoErrors();
    expect(($this->latest)()->product_code)->toBe('AO012'); // tự viết hoa; mã gần nhất

    $this->get('/admin/catalog/products/create')->assertInertia(fn (Assert $page) => $page->where('suggestedProductCode', 'AO014'));
    $this->get('/admin/catalog/products/'.($this->latest)()->id.'/edit')->assertInertia(fn (Assert $page) => $page->where('suggestedProductCode', null));
});

it('có tiền tố trong Admin → Cài đặt → Cửa hàng: tiền tố + số lớn nhất của các mã cùng tiền tố cộng 1, theo số chữ số', function () {
    app(Settings::class)->set('core', 'catalog.product_code_prefix', 'sp');
    app(Settings::class)->set('core', 'catalog.product_code_digits', 3);
    $suggested = fn (): string => $this->get('/admin/catalog/products/create')->viewData('page')['props']['suggestedProductCode'];

    expect($suggested())->toBe('SP001');
    foreach (['SP007', 'SPX12', 'AO050'] as $code) {
        T::product(null, ['product_code' => $code]); // AO050 tạo sau cùng nhưng không cùng tiền tố; SPX12 không phải SP<số>
    }
    expect($suggested())->toBe('SP008');

    ($this->create)()->assertSessionHasNoErrors();
    expect(($this->latest)()->product_code)->toBe('SP008');
    $this->put('/admin/settings', ['namespace' => 'core', 'values' => ['catalog.product_code_prefix' => 'S P']])->assertSessionHasErrors('values.catalog.product_code_prefix');
});

it('slug trống → theo tên, không dấu, trùng thì thêm -2; slug gõ có dấu/khoảng trắng → chuẩn hoá; slug trùng do người dùng gõ vẫn báo lỗi', function () {
    ($this->create)(['product_code' => 'A1'])->assertSessionHasNoErrors();
    ($this->create)(['product_code' => 'A2'])->assertSessionHasNoErrors();
    expect(T::seed(fn () => Product::query()->orderBy('id')->pluck('slug')->all()))->toBe(['ao-so-mi-linen', 'ao-so-mi-linen-2']);

    ($this->create)(['product_code' => 'A3', 'slug' => 'Đầm Lụa  Mùa Hè'])->assertSessionHasNoErrors();
    expect(($this->latest)()->slug)->toBe('dam-lua-mua-he');
    ($this->create)(['product_code' => 'A4', 'slug' => 'dam-lua-mua-he'])->assertSessionHasErrors('slug');
});

it('chỉ chọn một danh mục → làm danh mục chính; chọn nhiều thì để người dùng chọn', function () {
    $other = T::seed(fn () => Category::factory()->create());

    ($this->create)(['product_code' => 'B1', 'category_ids' => [$this->category->id]])->assertSessionHasNoErrors();
    expect(($this->latest)()->primary_category_id)->toBe($this->category->id);

    ($this->create)(['product_code' => 'B2', 'category_ids' => [$this->category->id, $other->id]])->assertSessionHasNoErrors();
    expect(($this->latest)()->primary_category_id)->toBeNull();
});
