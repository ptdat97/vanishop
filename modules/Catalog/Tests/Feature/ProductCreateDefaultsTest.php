<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

/*
| Thêm sản phẩm bớt nhập tay: mã gợi ý (mã kế tiếp của mã gần nhất), mã tự viết hoa, slug trống → theo tên không trùng,
| slug gõ có dấu → chuẩn hoá, một danh mục → danh mục chính.
*/

beforeEach(function () {
    $this->actingAs(T::staff(), 'staff');
    $this->category = T::seed(fn () => Category::factory()->create());
    $this->create = fn (array $overrides = []) => $this->post('/admin/catalog/products', array_replace(
        ['style_code' => '', 'slug' => '', 'status' => 'draft', 'category_ids' => [], 'translations' => ['vi' => ['name' => 'Áo sơ mi Linen']]],
        $overrides,
    ));
    $this->latest = fn (): Style => T::seed(fn () => Style::query()->latest('id')->firstOrFail());
});

it('mã gợi ý: chưa có sản phẩm → SP0001; sau đó tăng số cuối của mã gần nhất, giữ độ dài, bỏ mã đã có', function () {
    $this->get('/admin/catalog/products/create')->assertInertia(fn (Assert $page) => $page->where('suggestedStyleCode', 'SP0001'));
    ($this->create)()->assertSessionHasNoErrors();
    expect(($this->latest)()->style_code)->toBe('SP0001');

    T::product(null, ['style_code' => 'AO013']); // đã có từ trước
    ($this->create)(['style_code' => 'ao012'])->assertSessionHasNoErrors();
    expect(($this->latest)()->style_code)->toBe('AO012'); // tự viết hoa; mã gần nhất

    $this->get('/admin/catalog/products/create')->assertInertia(fn (Assert $page) => $page->where('suggestedStyleCode', 'AO014'));
    $this->get('/admin/catalog/products/'.($this->latest)()->id.'/edit')->assertInertia(fn (Assert $page) => $page->where('suggestedStyleCode', null));
});

it('slug trống → theo tên, không dấu, trùng thì thêm -2; slug gõ có dấu/khoảng trắng → chuẩn hoá; slug trùng do người dùng gõ vẫn báo lỗi', function () {
    ($this->create)(['style_code' => 'A1'])->assertSessionHasNoErrors();
    ($this->create)(['style_code' => 'A2'])->assertSessionHasNoErrors();
    expect(T::seed(fn () => Style::query()->orderBy('id')->pluck('slug')->all()))->toBe(['ao-so-mi-linen', 'ao-so-mi-linen-2']);

    ($this->create)(['style_code' => 'A3', 'slug' => 'Đầm Lụa  Mùa Hè'])->assertSessionHasNoErrors();
    expect(($this->latest)()->slug)->toBe('dam-lua-mua-he');
    ($this->create)(['style_code' => 'A4', 'slug' => 'dam-lua-mua-he'])->assertSessionHasErrors('slug');
});

it('chỉ chọn một danh mục → làm danh mục chính; chọn nhiều thì để người dùng chọn', function () {
    $other = T::seed(fn () => Category::factory()->create());

    ($this->create)(['style_code' => 'B1', 'category_ids' => [$this->category->id]])->assertSessionHasNoErrors();
    expect(($this->latest)()->primary_category_id)->toBe($this->category->id);

    ($this->create)(['style_code' => 'B2', 'category_ids' => [$this->category->id, $other->id]])->assertSessionHasNoErrors();
    expect(($this->latest)()->primary_category_id)->toBeNull();
});
