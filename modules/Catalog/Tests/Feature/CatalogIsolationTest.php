<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

beforeEach(function () {
    $this->staff = T::staff();
});

it('trang Catalog vào thẳng danh sách sản phẩm (một cửa hàng, không chọn brand)', function () {
    $this->actingAs($this->staff, 'staff')->get('/admin/catalog')->assertRedirect('/admin/catalog/products');
});

it('danh mục cha không tồn tại bị từ chối', function () {
    $this->actingAs($this->staff, 'staff')->post('/admin/catalog/categories', [
        'slug' => 'x', 'parent_id' => 999_999, 'status' => 'active', 'position' => 0,
        'translations' => ['vi' => ['name' => 'X']],
    ])->assertSessionHasErrors('parent_id');
});

it('chỉ có quyền xem thì không tạo/sửa được', function () {
    $viewer = T::staff(['admin.access', 'catalog.view']);

    $this->actingAs($viewer, 'staff')->get('/admin/catalog/categories')->assertOk();
    $this->get('/admin/catalog/categories/create')->assertForbidden();
    $this->post('/admin/catalog/categories', [])->assertForbidden();
    $this->post('/admin/catalog/brands', [])->assertForbidden();
});

it('quản lý thương hiệu: tạo, sửa, chống trùng mã/slug, không xoá brand còn sản phẩm', function () {
    $this->actingAs($this->staff, 'staff');
    $payload = fn (array $overrides = []) => array_replace(['code' => 'LM', 'slug' => 'lumiere', 'name' => 'Lumière', 'status' => 'active', 'position' => 0], $overrides);

    $this->post('/admin/catalog/brands', $payload())->assertSessionHasNoErrors();
    $this->post('/admin/catalog/brands', $payload())->assertSessionHasErrors(['code', 'slug']);

    $brand = Brand::query()->where('slug', 'lumiere')->sole();
    $this->put("/admin/catalog/brands/{$brand->id}", $payload(['name' => 'Lumière Paris']))->assertSessionHasNoErrors();
    expect($brand->fresh()->name)->toBe('Lumière Paris');

    T::product($brand->id, ['style_code' => 'LM-01']);
    $this->delete("/admin/catalog/brands/{$brand->id}")->assertSessionHasErrors('brand');

    $this->get('/admin/catalog/brands')->assertInertia(fn (Assert $page) => $page->component('Catalog::Taxonomy/Brands')
        ->has('brands', 1)->where('brands.0.styles_count', 1));

    $empty = Brand::factory()->create();
    $this->delete("/admin/catalog/brands/{$empty->id}")->assertSessionHasNoErrors();
    expect(Brand::query()->whereKey($empty->id)->exists())->toBeFalse();
});

it('sản phẩm gắn thương hiệu qua form; brand không tồn tại bị từ chối', function () {
    $brand = Brand::factory()->create();
    $category = T::seed(fn () => Category::factory()->create());
    $this->actingAs($this->staff, 'staff');
    $product = fn (array $overrides = []) => array_replace(['style_code' => 'ST-01', 'slug' => 'st-01', 'status' => 'draft', 'category_ids' => [$category->id], 'translations' => ['vi' => ['name' => 'Áo']]], $overrides);

    $this->post('/admin/catalog/products', $product(['brand_id' => 999_999]))->assertSessionHasErrors('brand_id');
    $this->post('/admin/catalog/products', $product(['brand_id' => $brand->id]))->assertSessionHasNoErrors();

    expect(T::seed(fn () => Style::query()->where('style_code', 'ST-01')->value('brand_id')))->toBe($brand->id);
});
