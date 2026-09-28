<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->other = Brand::factory()->create(['slug' => 'urbanx']);
    $this->base = '/admin/catalog/lumiere/products';
    $this->actingAs(T::staffFor($this->brand), 'staff');

    [$this->shirts, $this->material, $this->silk, $this->care] = T::seed(function () {
        $shirts = Category::factory()->create(['brand_id' => $this->brand->id, 'slug' => 'ao-so-mi']);
        $material = Attribute::factory()->create(['brand_id' => $this->brand->id, 'code' => 'material', 'input_type' => 'select', 'is_filterable' => true]);
        $silk = $material->values()->create(['code' => 'silk', 'position' => 0]);
        $care = Attribute::factory()->create(['brand_id' => $this->brand->id, 'code' => 'fit', 'input_type' => 'text']);

        return [$shirts, $material, $silk, $care];
    });
});

function productPayload(array $overrides = []): array
{
    return array_replace([
        'style_code' => 'LM24-SH012',
        'slug' => 'ao-so-mi-lua',
        'status' => 'active',
        'translations' => ['vi' => ['name' => 'Áo sơ mi lụa', 'description' => 'Mềm mại'], 'en' => ['name' => 'Silk shirt']],
        'category_ids' => [],
        'primary_category_id' => null,
        'attributes' => [],
    ], $overrides);
}

it('tạo sản phẩm với danh mục, thuộc tính và chuỗi tìm kiếm không dấu', function () {
    $this->post($this->base, productPayload([
        'category_ids' => [$this->shirts->id],
        'primary_category_id' => $this->shirts->id,
        'attributes' => [$this->material->id => (string) $this->silk->id, $this->care->id => 'Regular fit'],
    ]))->assertSessionHasNoErrors()->assertRedirect();

    T::seed(function () {
        $style = Style::query()->with(['categories', 'attributeValues'])->sole();
        expect($style->search_text)->toBe('lm24 sh012 ao so mi lua silk shirt')
            ->and($style->categories->pluck('id')->all())->toBe([$this->shirts->id])
            ->and($style->attributeValues->pluck('attribute_value_id')->filter()->values()->all())->toBe([$this->silk->id])
            ->and($style->attributeValues->pluck('value_text')->filter()->values()->all())->toBe(['Regular fit']);
    });
});

it('từ chối danh mục, thuộc tính, giá trị của brand khác hoặc sai kiểu', function () {
    $foreignCategory = T::seed(fn () => Category::factory()->create(['brand_id' => $this->other->id]));
    $foreignAttribute = T::seed(fn () => Attribute::factory()->create(['brand_id' => $this->other->id]));

    $cases = [
        'category_ids' => productPayload(['category_ids' => [$foreignCategory->id]]),
        'primary_category_id' => productPayload(['primary_category_id' => $this->shirts->id]),
        "attributes.{$this->material->id}" => productPayload(['attributes' => [$this->material->id => '999999']]),
        "attributes.{$foreignAttribute->id}" => productPayload(['attributes' => [$foreignAttribute->id => 'x']]),
        'published_to' => productPayload(['published_from' => '2026-11-01 00:00', 'published_to' => '2026-10-01 00:00']),
        'style_code' => productPayload(['style_code' => 'áo 1']),
    ];

    foreach ($cases as $field => $payload) {
        $this->post($this->base, $payload)->assertSessionHasErrors($field);
    }

    expect(T::seed(fn () => Style::query()->count()))->toBe(0);
});

it('mã và slug duy nhất trong brand', function () {
    T::product($this->brand->id, ['style_code' => 'LM24-SH012', 'slug' => 'ao-so-mi-lua']);

    $this->post($this->base, productPayload())->assertSessionHasErrors(['style_code', 'slug']);
});

it('cập nhật dùng lock_version, chỉ xoá được bản nháp', function () {
    $style = T::product($this->brand->id, ['style_code' => 'LM24-SH012', 'slug' => 'ao-so-mi-lua']);
    $payload = productPayload(['lock_version' => 0, 'status' => 'archived']);

    $this->put("{$this->base}/{$style->id}", $payload)->assertSessionHasNoErrors();
    $this->put("{$this->base}/{$style->id}", $payload)->assertSessionHasErrors('lock_version');
    $this->delete("{$this->base}/{$style->id}")->assertSessionHasErrors('status');

    $draft = T::product($this->brand->id, ['status' => 'draft']);
    $this->delete("{$this->base}/{$draft->id}")->assertRedirect();
    expect(T::seed(fn () => Style::query()->find($draft->id)))->toBeNull();
});

it('thêm màu, tải ảnh, đổi thứ tự và xoá ảnh', function () {
    Storage::fake('public');
    $style = T::product($this->brand->id);
    $color = T::seed(fn () => Color::factory()->create(['brand_id' => $this->brand->id, 'code' => 'IVR']));

    $this->post("{$this->base}/{$style->id}/colors", ['color_id' => $color->id])->assertSessionHasNoErrors();
    $this->post("{$this->base}/{$style->id}/colors", ['color_id' => $color->id])->assertSessionHasErrors('color_id');
    $styleColor = T::seed(fn () => $style->colors()->sole());

    $this->post("{$this->base}/{$style->id}/colors/{$styleColor->id}/images", [
        'images' => [UploadedFile::fake()->image('a.jpg', 1600, 2000), UploadedFile::fake()->image('b.jpg', 1600, 2001)],
    ])->assertSessionHasNoErrors();

    $ids = T::seed(fn () => $styleColor->gallery()->pluck('id')->all());
    $this->put("{$this->base}/{$style->id}/colors/{$styleColor->id}/images/order", ['order' => array_reverse($ids)])->assertSessionHasNoErrors();
    expect(T::seed(fn () => $styleColor->gallery()->pluck('id')->all()))->toBe(array_reverse($ids));

    $this->delete("{$this->base}/{$style->id}/colors/{$styleColor->id}/images/{$ids[0]}")->assertSessionHasNoErrors();
    expect(T::seed(fn () => $styleColor->gallery()->count()))->toBe(1);

    $this->get("{$this->base}/{$style->id}/edit")->assertInertia(fn (Assert $page) => $page
        ->component('Catalog::Products/Form')->where('product.colors.0.code', 'IVR')->has('product.colors.0.images', 1));
});

it('không thao tác được màu/ảnh của sản phẩm khác qua URL', function () {
    $mine = T::product($this->brand->id);
    $otherProduct = T::product($this->brand->id);
    $color = T::seed(fn () => Color::factory()->create(['brand_id' => $this->brand->id]));
    $foreignColor = T::seed(fn () => $otherProduct->colors()->create(['color_id' => $color->id]));
    $colorOtherBrand = T::seed(fn () => Color::factory()->create(['brand_id' => $this->other->id]));

    $this->delete("{$this->base}/{$mine->id}/colors/{$foreignColor->id}")->assertNotFound();
    $this->post("{$this->base}/{$mine->id}/colors", ['color_id' => $colorOtherBrand->id])->assertSessionHasErrors('color_id');
});

it('danh sách sản phẩm tìm được theo tên không dấu', function () {
    T::product($this->brand->id, ['name' => 'Đầm lụa đen']);
    T::product($this->brand->id, ['name' => 'Quần jeans']);
    T::product($this->other->id, ['name' => 'Đầm của brand khác']);

    $this->get("{$this->base}?q=dam")->assertInertia(fn (Assert $page) => $page
        ->component('Catalog::Products/Index')->where('products.total', 1)->where('products.data.0.name', 'Đầm lụa đen'));
});
