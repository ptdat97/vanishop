<?php

use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

/*
| Stabilization A4: danh sách sản phẩm lọc theo danh mục (gồm danh mục con), thương hiệu, bộ sưu tập; thao tác hàng loạt gán/gỡ
| danh mục, đổi thương hiệu, thêm/gỡ bộ sưu tập, xoá bản nháp.
*/

beforeEach(function () {
    $this->actingAs(T::staff(), 'staff');
    [$this->lumiere, $this->urbanx, $this->women, $this->dresses, $this->sale] = T::seed(function () {
        $women = Category::factory()->create(['slug' => 'nu']);
        $dresses = Category::factory()->create(['slug' => 'dam', 'parent_id' => $women->id]);
        $sale = ProductCollection::query()->create(['slug' => 'sale-he', 'status' => 'active', 'position' => 0]);

        return [Brand::factory()->create(['slug' => 'lumiere']), Brand::factory()->create(['slug' => 'urbanx']), $women, $dresses, $sale];
    });
    $this->dress = T::product($this->lumiere->id, ['style_code' => 'DAM01', 'category_ids' => [$this->dresses->id], 'primary_category_id' => $this->dresses->id]);
    $this->shirt = T::product($this->urbanx->id, ['style_code' => 'AO01']);
    $this->plain = T::product(null, ['style_code' => 'TUI01', 'status' => 'draft']);
    $this->codes = fn (array $query): array => collect($this->get('/admin/catalog/products?'.http_build_query($query))->assertOk()
        ->viewData('page')['props']['products']['data'])->pluck('style_code')->sort()->values()->all();
});

it('lọc theo danh mục (gồm danh mục con), thương hiệu (cả "chưa có"), bộ sưu tập; trang có lựa chọn và thao tác hàng loạt', function () {
    T::seed(fn () => $this->sale->styles()->attach($this->shirt->id, ['position' => 0]));

    expect(($this->codes)(['category' => $this->women->id]))->toBe(['DAM01'])
        ->and(($this->codes)(['brand' => $this->urbanx->id]))->toBe(['AO01'])
        ->and(($this->codes)(['brand' => 'none']))->toBe(['TUI01'])
        ->and(($this->codes)(['collection' => $this->sale->id]))->toBe(['AO01'])
        ->and(($this->codes)(['category' => $this->women->id, 'brand' => $this->urbanx->id]))->toBe([]);

    $this->get('/admin/catalog/products')->assertInertia(fn (Assert $page) => $page
        ->where('filterOptions.categories.1.label', '— '.$this->dresses->translate('name'))
        ->has('filterOptions.collections', 1)
        ->where('extensions.actions', fn ($actions): bool => collect($actions)->pluck('key')->intersect(['category_add', 'brand', 'collection_add', 'delete'])->count() === 4));
    $this->get('/admin/catalog/products?brand=abc')->assertSessionHasErrors('brand');
});

it('gán/gỡ danh mục hàng loạt: danh mục chính tự đặt khi chưa có, gỡ danh mục chính thì lấy danh mục còn lại; phát ProductUpdated', function () {
    Event::fake([ProductUpdated::class]);
    $ids = [$this->dress->id, $this->shirt->id];

    $this->post('/admin/catalog/products/bulk-edit', ['ids' => $ids, 'action' => 'category_add', 'target_id' => $this->women->id])
        ->assertRedirect()->assertSessionHas('success', 'Đã cập nhật 2 sản phẩm.');
    expect($this->shirt->fresh())->primary_category_id->toBe($this->women->id)->lock_version->toBe(1)
        ->and($this->dress->fresh()->primary_category_id)->toBe($this->dresses->id)
        ->and($this->dress->fresh()->categories()->pluck('categories.id')->sort()->values()->all())->toBe([$this->women->id, $this->dresses->id]);
    Event::assertDispatchedTimes(ProductUpdated::class, 2);

    // Đã có rồi: không tính.
    $this->post('/admin/catalog/products/bulk-edit', ['ids' => $ids, 'action' => 'category_add', 'target_id' => $this->women->id])->assertSessionHas('success', 'Đã cập nhật 0 sản phẩm.');

    $this->post('/admin/catalog/products/bulk-edit', ['ids' => [$this->dress->id], 'action' => 'category_remove', 'target_id' => $this->dresses->id]);
    expect($this->dress->fresh()->primary_category_id)->toBe($this->women->id);
    $this->post('/admin/catalog/products/bulk-edit', ['ids' => [$this->dress->id], 'action' => 'category_remove', 'target_id' => $this->women->id]);
    expect($this->dress->fresh()->primary_category_id)->toBeNull();
});

it('đổi/bỏ thương hiệu, thêm/gỡ bộ sưu tập (thêm vào cuối bộ sưu tập)', function () {
    T::seed(fn () => $this->sale->styles()->attach($this->plain->id, ['position' => 4]));

    $this->post('/admin/catalog/products/bulk-edit', ['ids' => [$this->shirt->id, $this->plain->id], 'action' => 'brand', 'target_id' => $this->lumiere->id]);
    expect(Style::query()->whereKey([$this->shirt->id, $this->plain->id])->pluck('brand_id')->unique()->all())->toBe([$this->lumiere->id]);
    $this->post('/admin/catalog/products/bulk-edit', ['ids' => [$this->shirt->id], 'action' => 'brand', 'target_id' => null])->assertSessionHasNoErrors();
    expect($this->shirt->fresh()->brand_id)->toBeNull();

    $this->post('/admin/catalog/products/bulk-edit', ['ids' => [$this->shirt->id], 'action' => 'collection_add', 'target_id' => $this->sale->id]);
    expect($this->sale->styles()->where('styles.id', $this->shirt->id)->value('collection_style.position'))->toBe(5);
    $this->post('/admin/catalog/products/bulk-edit', ['ids' => [$this->shirt->id, $this->plain->id], 'action' => 'collection_remove', 'target_id' => $this->sale->id])
        ->assertSessionHas('success', 'Đã cập nhật 2 sản phẩm.');
    expect($this->sale->styles()->count())->toBe(0);
});

it('dữ liệu sai: thiếu/không có danh mục, thao tác lạ → lỗi, không đổi gì', function () {
    $this->post('/admin/catalog/products/bulk-edit', ['ids' => [$this->shirt->id], 'action' => 'category_add'])->assertSessionHasErrors('target_id');
    $this->post('/admin/catalog/products/bulk-edit', ['ids' => [$this->shirt->id], 'action' => 'category_add', 'target_id' => 999_999])->assertSessionHasErrors('target_id');
    $this->post('/admin/catalog/products/bulk-edit', ['ids' => [$this->shirt->id], 'action' => 'xoa_het', 'target_id' => $this->women->id])->assertSessionHasErrors('action');
    expect($this->shirt->fresh()->lock_version)->toBe(0);
});

it('xoá hàng loạt chỉ xoá bản nháp, báo số sản phẩm bỏ qua', function () {
    $this->post('/admin/catalog/products/bulk-delete', ['ids' => [$this->plain->id, $this->shirt->id]])
        ->assertSessionHas('success', 'Đã xoá 1 sản phẩm nháp; bỏ qua 1 sản phẩm đã mở bán hoặc ngừng bán (dùng "Ngừng bán" để ẩn).');
    expect(Style::query()->whereKey($this->plain->id)->exists())->toBeFalse()
        ->and(Style::query()->whereKey($this->shirt->id)->exists())->toBeTrue();
});

it('chỉ xem catalog: không có thao tác hàng loạt, gọi trực tiếp bị 403', function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff');

    $this->get('/admin/catalog/products')->assertInertia(fn (Assert $page) => $page->where('extensions.actions', fn ($actions): bool => ! collect($actions)->pluck('key')->contains('category_add')));
    $this->post('/admin/catalog/products/bulk-edit', ['ids' => [$this->shirt->id], 'action' => 'brand', 'target_id' => $this->lumiere->id])->assertForbidden();
    $this->post('/admin/catalog/products/bulk-delete', ['ids' => [$this->plain->id]])->assertForbidden();
    expect($this->shirt->fresh()->brand_id)->toBe($this->urbanx->id);
});

it('báo và lọc sản phẩm thiếu dữ liệu: chưa có SKU, ảnh, danh mục, khối lượng SKU đang bán, mô tả', function () {
    T::seed(function () {
        $this->dress->syncTranslations(['vi' => ['name' => 'Đầm', 'description' => 'Đầm lụa mềm']]);
        app(ProductOptions::class)->createVariant($this->dress, [], ['weight_gram' => 300]);
        app(ProductOptions::class)->createVariant($this->shirt, [], ['weight_gram' => null]);
        $media = Media::query()->create(['disk' => 'public', 'path' => 'media/d.jpg', 'folder' => '', 'original_name' => 'd.jpg', 'mime_type' => 'image/jpeg', 'size_bytes' => 1, 'width' => 1, 'height' => 1, 'checksum' => hash('sha256', 'd')]);
        $this->dress->variants()->sole()->gallery()->create(['media_id' => $media->id, 'role' => 'gallery', 'position' => 1]);
    });

    expect(($this->codes)(['missing' => 'image']))->toBe(['AO01', 'TUI01'])
        ->and(($this->codes)(['missing' => 'variants']))->toBe(['TUI01'])
        ->and(($this->codes)(['missing' => 'weight']))->toBe(['AO01'])
        ->and(($this->codes)(['missing' => 'category']))->toBe(['AO01', 'TUI01'])
        ->and(($this->codes)(['missing' => 'description']))->toBe(['AO01', 'TUI01']);

    $rows = collect($this->get('/admin/catalog/products')->viewData('page')['props']['products']['data'])->keyBy('style_code');
    expect($rows['DAM01']['missing'])->toBe([])
        ->and($rows['AO01']['missing'])->toBe(['Chưa có ảnh', 'Chưa có danh mục', 'SKU thiếu khối lượng', 'Chưa có mô tả']);
    $this->get('/admin/catalog/products?missing=gia')->assertSessionHasErrors('missing');
});
