<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Tests\Feature\PricingTestHelpers;

require_once __DIR__.'/CatalogTestHelpers.php';
require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->other = Brand::factory()->create(['slug' => 'urbanx']);
    $this->base = '/admin/catalog/products';
    $this->actingAs(T::staff(), 'staff');

    [$this->shirts, $this->material, $this->silk, $this->care] = T::seed(function () {
        $shirts = Category::factory()->create(['slug' => 'ao-so-mi']);
        $material = Attribute::factory()->create(['code' => 'material', 'input_type' => 'select', 'is_filterable' => true]);
        $silk = $material->values()->create(['code' => 'silk', 'position' => 0]);
        $care = Attribute::factory()->create(['code' => 'fit', 'input_type' => 'text']);

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

it('từ chối danh mục, thuộc tính không tồn tại hoặc sai kiểu', function () {

    $cases = [
        'category_ids' => productPayload(['category_ids' => [999_999]]),
        'primary_category_id' => productPayload(['primary_category_id' => $this->shirts->id]),
        "attributes.{$this->material->id}" => productPayload(['attributes' => [$this->material->id => '999999']]),
        'attributes.999999' => productPayload(['attributes' => [999_999 => 'x']]),
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

    // Một người lưu nhiều lần liên tiếp: form lấy phiên bản mới server trả sau mỗi lần lưu (syncLockVersion) → không báo xung đột.
    $this->get("{$this->base}/{$style->id}/edit")->assertInertia(fn (Assert $page) => $page->where('product.lock_version', 1));
    $this->put("{$this->base}/{$style->id}", [...$payload, 'lock_version' => 1])->assertSessionHasNoErrors();
    $this->put("{$this->base}/{$style->id}", [...$payload, 'lock_version' => 2])->assertSessionHasNoErrors();

    $draft = T::product($this->brand->id, ['status' => 'draft']);
    $this->delete("{$this->base}/{$draft->id}")->assertRedirect();
    expect(T::seed(fn () => Style::query()->find($draft->id)))->toBeNull();
});

it('ảnh theo SKU: tải lên, đổi thứ tự, áp cho mọi SKU cùng màu, xoá ảnh', function () {
    Storage::fake('public');
    $style = T::product($this->brand->id);
    [$small, $medium] = PricingTestHelpers::variants($style, ['S', 'M']);
    $images = "{$this->base}/{$style->id}/variants/{$small->id}/images";

    $this->post($images, ['images' => [UploadedFile::fake()->image('a.jpg', 1600, 2000), UploadedFile::fake()->image('b.jpg', 1600, 2001)]])->assertSessionHasNoErrors();
    $ids = T::seed(fn () => $small->gallery()->pluck('id')->all());
    $this->put("{$images}/order", ['order' => array_reverse($ids)])->assertSessionHasNoErrors();
    expect(T::seed(fn () => $small->gallery()->pluck('id')->all()))->toBe(array_reverse($ids));

    $colorType = T::seed(fn () => OptionType::query()->where('code', OptionType::COLOR)->value('id'));
    $this->post("{$images}/copy", ['option_type_id' => $colorType])->assertSessionHasNoErrors();
    expect(T::seed(fn () => $medium->gallery()->pluck('media_id')->all()))->toBe(T::seed(fn () => $small->gallery()->pluck('media_id')->all()));

    $this->delete("{$images}/{$ids[0]}")->assertSessionHasNoErrors();
    expect(T::seed(fn () => $small->gallery()->count()))->toBe(1);

    $this->get("{$this->base}/{$style->id}/edit")->assertInertia(fn (Assert $page) => $page
        ->component('Catalog::Products/Form')->where('product.variants.0.sku', $small->sku)->has('product.variants.0.images', 1)->has('product.variants.1.images', 2));
});

it('không thao tác được ảnh SKU của sản phẩm khác qua URL', function () {
    $mine = T::product($this->brand->id);
    $otherProduct = T::product($this->brand->id);
    [$foreign] = PricingTestHelpers::variants($otherProduct, ['S']);

    $this->post("{$this->base}/{$mine->id}/variants/{$foreign->id}/images/library", ['media_ids' => [1]])->assertNotFound();
});

it('danh sách sản phẩm tìm được theo tên không dấu', function () {
    T::product($this->brand->id, ['name' => 'Đầm lụa đen']);
    T::product($this->brand->id, ['name' => 'Quần jeans']);
    T::product($this->other->id, ['name' => 'Áo thun brand khác']);

    $this->get("{$this->base}?q=dam")->assertInertia(fn (Assert $page) => $page
        ->component('Catalog::Products/Index')->where('products.total', 1)->where('products.data.0.name', 'Đầm lụa đen')->where('products.per_page', 30));
    $this->get("{$this->base}?per_page=50")->assertInertia(fn (Assert $page) => $page->where('products.per_page', 50));
    $this->get("{$this->base}?per_page=101")->assertSessionHasErrors('per_page');
});

it('danh sách sản phẩm sắp theo cột được phép; khoá lạ giữ thứ tự mặc định (mới cập nhật trước)', function () {
    T::seed(function () {
        Style::factory()->create(['style_code' => 'B-200', 'updated_at' => now()->subDay()]);
        Style::factory()->create(['style_code' => 'A-100', 'updated_at' => now()->subDays(2)]);
        Style::factory()->create(['style_code' => 'C-300', 'updated_at' => now()]);
    });
    $codes = fn (string $query): array => $this->get("{$this->base}?{$query}")->assertOk()->viewData('page')['props']['products']['data'];

    expect(array_column($codes('sort=style_code&direction=asc'), 'style_code'))->toBe(['A-100', 'B-200', 'C-300'])
        ->and(array_column($codes('sort=style_code&direction=desc'), 'style_code'))->toBe(['C-300', 'B-200', 'A-100'])
        ->and(array_column($codes('sort=search_text&direction=asc'), 'style_code'))->toBe(['C-300', 'B-200', 'A-100']);

    $this->get("{$this->base}?sort=style_code&direction=sideways")->assertInertia(fn (Assert $page) => $page->where('sort', ['key' => 'style_code', 'direction' => 'desc']));
    $this->get("{$this->base}?sort=search_text")->assertInertia(fn (Assert $page) => $page->where('sort', null));
});

it('cờ theo dõi tồn kho: mặc định bật; tắt khi tạo, bật lại khi sửa; lưu không gửi cờ thì giữ nguyên', function () {
    $this->post($this->base, productPayload(['track_inventory' => false]))->assertSessionHasNoErrors();
    $style = T::seed(fn () => Style::query()->sole());
    expect($style->track_inventory)->toBeFalse();
    $this->get("{$this->base}/{$style->id}/edit")->assertInertia(fn (Assert $page) => $page->where('product.track_inventory', false));

    $this->put("{$this->base}/{$style->id}", productPayload(['lock_version' => 0]))->assertSessionHasNoErrors();
    expect($style->fresh()->track_inventory)->toBeFalse();
    $this->put("{$this->base}/{$style->id}", productPayload(['lock_version' => 1, 'track_inventory' => true]))->assertSessionHasNoErrors();
    expect($style->fresh()->track_inventory)->toBeTrue();
});
