<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Persistence\Models\PriceHistory;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/CatalogTestHelpers.php';
require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Stabilization A4: nhân bản sản phẩm thành bản nháp — chép nội dung, phân loại, thuộc tính, biến thể (SKU mới), ảnh, video, giá
| niêm yết; không chép tồn kho, bộ sưu tập, slug theo ngôn ngữ.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'pricing.view']), 'staff');
    [$brand, $category, $material, $silk] = T::seed(function () {
        $material = Attribute::factory()->create(['code' => 'material', 'input_type' => 'select']);

        return [Brand::factory()->create(), Category::factory()->create(['slug' => 'dam']), $material, $material->values()->create(['code' => 'silk', 'position' => 0])];
    });
    $this->source = T::product($brand->id, [
        'style_code' => 'DAM01', 'slug' => 'dam-lua', 'name' => 'Đầm lụa', 'category_ids' => [$category->id], 'primary_category_id' => $category->id,
        'translations' => ['vi' => ['name' => 'Đầm lụa', 'description' => 'Mềm mại'], 'en' => ['name' => 'Silk dress', 'slug' => 'silk-dress']],
        'attributes' => [$material->id => $silk->id],
    ]);
    $this->variants = P::variants($this->source, ['S', 'M']);
    $this->media = T::seed(fn () => Media::query()->create(['disk' => 'public', 'path' => 'media/a.jpg', 'folder' => '', 'original_name' => 'a.jpg', 'mime_type' => 'image/jpeg', 'size_bytes' => 1, 'width' => 800, 'height' => 1200, 'checksum' => hash('sha256', 'a')]));
    T::seed(function () {
        $this->variants[0]->gallery()->create(['media_id' => $this->media->id, 'role' => 'gallery', 'position' => 1]);
        $this->variants[1]->update(['weight_gram' => 350, 'barcode' => '8930000000017']);
    });
    P::priceList(['code' => 'base'], [$this->variants[0]->id => [500_000, 650_000], $this->variants[1]->id => [520_000]]);
    $this->duplicate = fn (array $data) => $this->post("/admin/catalog/products/{$this->source->id}/duplicate", $data);
});

it('nhân bản: bản nháp với mã/tên mới, cùng phân loại, thuộc tính, trục, SKU mới (barcode trống), ảnh, giá niêm yết; không chép tồn', function () {
    $response = ($this->duplicate)(['style_code' => 'DAM02', 'name' => 'Đầm lụa xanh']);

    $copy = Style::query()->where('style_code', 'DAM02')->sole();
    $response->assertRedirect("/admin/catalog/products/{$copy->id}/edit")->assertSessionHas('success', 'Đã tạo bản nháp DAM02. Kiểm tra lại ảnh, giá rồi mở bán.');
    expect($copy)->status->value->toBe('draft')->slug->toBe('dam-lua-xanh')->brand_id->toBe($this->source->brand_id)
        ->primary_category_id->toBe($this->source->primary_category_id)
        ->and($copy->translate('name'))->toBe('Đầm lụa xanh')
        ->and($copy->translate('description'))->toBe('Mềm mại')
        ->and($copy->translate('name', 'en'))->toBe('Silk dress')
        ->and(DB::table('style_translations')->where('style_id', $copy->id)->where('locale', 'en')->value('slug'))->toBeNull()
        ->and(DB::table('style_attribute_values')->where('style_id', $copy->id)->value('attribute_value_id'))->toBe(DB::table('style_attribute_values')->where('style_id', $this->source->id)->value('attribute_value_id'));

    $copies = Variant::query()->where('style_id', $copy->id)->orderBy('id')->get();
    expect($copies)->toHaveCount(2)
        ->and($copies->pluck('option_key')->all())->toBe(collect($this->variants)->pluck('option_key')->all())
        ->and($copies->pluck('sku')->every(fn (string $sku): bool => str_starts_with($sku, 'DAM02-')))->toBeTrue()
        ->and($copies[1])->weight_gram->toBe(350)->barcode->toBeNull()
        ->and($copies[0]->gallery()->value('media_id'))->toBe($this->media->id)
        ->and(DB::table('prices')->whereIn('variant_id', $copies->pluck('id'))->orderBy('variant_id')->get(['amount', 'compare_at_amount'])->map(fn ($row): array => [(int) $row->amount, $row->compare_at_amount === null ? null : (int) $row->compare_at_amount])->all())
        ->toBe([[500_000, 650_000], [520_000, null]])
        ->and(PriceHistory::query()->whereIn('variant_id', $copies->pluck('id'))->count())->toBe(2)
        ->and(DB::table('stock_levels')->whereIn('variant_id', $copies->pluck('id'))->sum('on_hand'))->toEqual(0);
});

it('slug trùng thì thêm số; mã trùng hoặc sai định dạng → lỗi, không tạo gì', function () {
    ($this->duplicate)(['style_code' => 'DAM02', 'name' => 'Đầm lụa'])->assertSessionHasNoErrors();
    expect(Style::query()->where('style_code', 'DAM02')->value('slug'))->toBe('dam-lua-2');

    ($this->duplicate)(['style_code' => 'DAM01', 'name' => 'X'])->assertSessionHasErrors('style_code');
    ($this->duplicate)(['style_code' => 'dam 03', 'name' => 'X'])->assertSessionHasErrors('style_code');
    expect(Style::query()->count())->toBe(2);
});

it('chỉ xem catalog → 403', function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff');
    ($this->duplicate)(['style_code' => 'DAM02', 'name' => 'X'])->assertForbidden();
});

it('sản phẩm đơn (không trục tuỳ chọn, một SKU) nhân bản thành sản phẩm đơn', function () {
    $single = T::product(null, ['style_code' => 'TUI01', 'name' => 'Túi']);
    T::seed(fn () => app(ProductOptions::class)->createVariant($single, []));

    $this->post("/admin/catalog/products/{$single->id}/duplicate", ['style_code' => 'TUI02', 'name' => 'Túi đen'])->assertSessionHasNoErrors();
    $copy = Style::query()->where('style_code', 'TUI02')->sole();
    expect($copy->options()->count())->toBe(0)
        ->and(Variant::query()->where('style_id', $copy->id)->pluck('sku')->all())->toBe(['TUI02']);
});
