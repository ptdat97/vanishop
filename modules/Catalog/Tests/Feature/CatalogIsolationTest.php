<?php

use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\StaffUser;

require_once __DIR__.'/CatalogTestHelpers.php';

beforeEach(function () {
    $this->lumiere = Brand::factory()->create(['slug' => 'lumiere']);
    $this->urbanx = Brand::factory()->create(['slug' => 'urbanx']);
    $this->staff = T::staffFor($this->lumiere);
});

it('nhân viên brand A không vào được workspace của brand B', function () {
    $this->actingAs($this->staff, 'staff')->get('/admin/catalog/urbanx/categories')->assertNotFound();
    $this->get('/admin/catalog/khong-ton-tai/categories')->assertNotFound();
});

it('không sửa được danh mục của brand khác qua URL của brand mình', function () {
    $foreign = T::seed(fn () => Category::factory()->create(['brand_id' => $this->urbanx->id]));

    $this->actingAs($this->staff, 'staff')->get("/admin/catalog/lumiere/categories/{$foreign->id}/edit")->assertNotFound();
    $this->delete("/admin/catalog/lumiere/categories/{$foreign->id}")->assertNotFound();
});

it('không gắn được danh mục cha thuộc brand khác', function () {
    $foreign = T::seed(fn () => Category::factory()->create(['brand_id' => $this->urbanx->id]));

    $this->actingAs($this->staff, 'staff')->post('/admin/catalog/lumiere/categories', [
        'slug' => 'x', 'parent_id' => $foreign->id, 'status' => 'active', 'position' => 0,
        'translations' => ['vi' => ['name' => 'X']],
    ])->assertSessionHasErrors('parent_id');
});

it('chỉ có quyền xem thì không tạo/sửa được', function () {
    $viewer = T::staffFor($this->lumiere, ['admin.access', 'catalog.view']);

    $this->actingAs($viewer, 'staff')->get('/admin/catalog/lumiere/categories')->assertOk();
    $this->get('/admin/catalog/lumiere/categories/create')->assertForbidden();
    $this->post('/admin/catalog/lumiere/categories', [])->assertForbidden();
});

it('trang Catalog tự vào brand duy nhất, hoặc cho chọn khi có nhiều brand', function () {
    $this->actingAs($this->staff, 'staff')->get('/admin/catalog')->assertRedirect('/admin/catalog/lumiere/products');

    $owner = StaffUser::factory()->withPermissions(['admin.access', 'catalog.view'])->create();
    $this->actingAs($owner, 'staff')->get('/admin/catalog')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('BrandPicker')->has('brands', 2));
});
