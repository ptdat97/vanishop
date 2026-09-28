<?php

use Illuminate\Support\Facades\Event;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Events\VariantCreated;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\Size;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->other = Brand::factory()->create();
    $this->actingAs(T::staffFor($this->brand), 'staff');
    $this->style = T::product($this->brand->id, ['style_code' => 'LM24-SH012']);
    $this->base = "/admin/catalog/lumiere/products/{$this->style->id}";

    T::seed(function () {
        $this->ivory = Color::factory()->create(['brand_id' => $this->brand->id, 'code' => 'IVR']);
        $this->black = Color::factory()->create(['brand_id' => $this->brand->id, 'code' => 'BLK']);
        $this->s = Size::factory()->create(['brand_id' => $this->brand->id, 'code' => 'S', 'sort_order' => 20]);
        $this->m = Size::factory()->create(['brand_id' => $this->brand->id, 'code' => 'M', 'sort_order' => 30]);
        $this->foreignSize = Size::factory()->create(['brand_id' => $this->other->id, 'code' => 'L']);
    });
});

it('cần có màu trước khi tạo biến thể', function () {
    $this->post("{$this->base}/variants/generate", ['size_ids' => [$this->s->id]])->assertSessionHasErrors('size_ids');
});

it('sinh ma trận màu × size với SKU chuẩn, không đụng biến thể đã có', function () {
    Event::fake([VariantCreated::class]);
    $this->post("{$this->base}/colors", ['color_id' => $this->ivory->id]);
    $this->post("{$this->base}/colors", ['color_id' => $this->black->id]);

    $this->post("{$this->base}/variants/generate", ['size_ids' => [$this->s->id]])->assertSessionHasNoErrors();
    $this->post("{$this->base}/variants/generate", ['size_ids' => [$this->s->id, $this->m->id]])->assertSessionHasNoErrors();

    $skus = T::seed(fn () => Variant::query()->orderBy('sku')->pluck('sku')->all());
    expect($skus)->toBe(['LM24-SH012-BLK-M', 'LM24-SH012-BLK-S', 'LM24-SH012-IVR-M', 'LM24-SH012-IVR-S']);
    Event::assertDispatchedTimes(VariantCreated::class, 4);
});

it('từ chối size của brand khác', function () {
    $this->post("{$this->base}/colors", ['color_id' => $this->ivory->id]);

    $this->post("{$this->base}/variants/generate", ['size_ids' => [$this->foreignSize->id]])->assertSessionHasErrors('size_ids');
});

it('báo trùng SKU với sản phẩm khác', function () {
    $this->post("{$this->base}/colors", ['color_id' => $this->ivory->id]);
    T::seed(function () {
        $otherStyle = Style::factory()->create(['brand_id' => $this->brand->id]);
        $styleColor = $otherStyle->colors()->create(['color_id' => $this->black->id]);
        Variant::query()->create(['brand_id' => $this->brand->id, 'style_id' => $otherStyle->id, 'style_color_id' => $styleColor->id, 'size_id' => $this->m->id, 'sku' => 'LM24-SH012-IVR-S']);
    });

    $this->post("{$this->base}/variants/generate", ['size_ids' => [$this->s->id]])->assertSessionHasErrors('size_ids');
});

it('sửa SKU, barcode, trạng thái; SKU và barcode duy nhất', function () {
    $this->post("{$this->base}/colors", ['color_id' => $this->ivory->id]);
    $this->post("{$this->base}/variants/generate", ['size_ids' => [$this->s->id, $this->m->id]]);
    [$first, $second] = T::seed(fn () => Variant::query()->orderBy('id')->get()->all());

    $this->put("{$this->base}/variants/{$first->id}", ['sku' => 'SKU-A', 'barcode' => '8930000000017', 'status' => 'inactive', 'weight_gram' => 250])->assertSessionHasNoErrors();
    $this->put("{$this->base}/variants/{$second->id}", ['sku' => 'SKU-A', 'status' => 'active'])->assertSessionHasErrors('sku');
    $this->put("{$this->base}/variants/{$second->id}", ['sku' => 'SKU-B', 'barcode' => '8930000000017', 'status' => 'active'])->assertSessionHasErrors('barcode');

    expect(T::seed(fn () => $first->refresh()->status->value))->toBe('inactive');
});

it('không sửa được biến thể của sản phẩm khác qua URL; không gỡ màu đã có biến thể', function () {
    $this->post("{$this->base}/colors", ['color_id' => $this->ivory->id]);
    $this->post("{$this->base}/variants/generate", ['size_ids' => [$this->s->id]]);
    $variant = T::seed(fn () => Variant::query()->sole());
    $other = T::product($this->brand->id);

    $this->put("/admin/catalog/lumiere/products/{$other->id}/variants/{$variant->id}", ['sku' => 'X', 'status' => 'active'])->assertNotFound();

    $styleColor = T::seed(fn () => $this->style->colors()->sole());
    $this->delete("{$this->base}/colors/{$styleColor->id}")->assertSessionHasErrors('color_id');
});

it('xoá sản phẩm nháp xoá luôn biến thể', function () {
    $draft = T::product($this->brand->id, ['status' => 'draft']);
    $this->post("/admin/catalog/lumiere/products/{$draft->id}/colors", ['color_id' => $this->ivory->id]);
    $this->post("/admin/catalog/lumiere/products/{$draft->id}/variants/generate", ['size_ids' => [$this->s->id]]);

    $this->delete("/admin/catalog/lumiere/products/{$draft->id}")->assertRedirect();

    expect(T::seed(fn () => Variant::query()->count()))->toBe(0);
});
