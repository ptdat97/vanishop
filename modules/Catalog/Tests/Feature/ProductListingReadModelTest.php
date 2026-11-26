<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Application\Categories\CategoryInput;
use Modules\Catalog\Application\Categories\CategoryService;
use Modules\Catalog\Application\Collections\CollectionService;
use Modules\Catalog\Application\Products\ProductService;
use Modules\Catalog\Application\Search\ProductListingProjector;
use Modules\Catalog\Application\Search\ProductSearch;
use Modules\Catalog\Application\Taxonomy\OptionTypeService;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Contracts\Data\ProductSearchQuery;
use Modules\Catalog\Contracts\Data\ProductSearchResult;
use Modules\Catalog\Contracts\ListPrices;
use Modules\Catalog\Domain\CategoryStatus;
use Modules\Catalog\Domain\ProductStatus;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Events\AvailabilityChanged;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/CatalogTestHelpers.php';
require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Read model trang danh sách (db-refactor §4.3): mỗi nguồn thay đổi tự cập nhật `product_listing` / `product_facet_values`
| (không qua T::seed — helper đó dựng lại mọi thứ, che mất nguồn bị bỏ sót).
*/

beforeEach(function () {
    $this->as = fn (Closure $work): mixed => app(CurrentContext::class)->runAs(ContextScope::system('test'), $work);
    $categories = app(CategoryService::class);
    $this->women = ($this->as)(fn () => $categories->create(new CategoryInput('nu', CategoryStatus::Active, ['vi' => ['name' => 'Nữ']])));
    $this->dresses = ($this->as)(fn () => $categories->create(new CategoryInput('dam', CategoryStatus::Active, ['vi' => ['name' => 'Đầm']], parentId: $this->women->id)));
    $this->men = ($this->as)(fn () => $categories->create(new CategoryInput('nam', CategoryStatus::Active, ['vi' => ['name' => 'Nam']])));
    $this->product = T::product(null, ['product_code' => 'DL01', 'category_ids' => [$this->dresses->id]]);
    $this->facets = fn (string $prefix): array => DB::table('product_facet_values')->where('product_id', $this->product->id)->where('facet', 'like', $prefix.'%')
        ->orderBy('facet')->orderBy('token')->get()->map(fn ($row): string => "{$row->facet}={$row->token}")->all();
    $this->listing = fn (): object => DB::table('product_listing')->where('product_id', $this->product->id)->sole();
});

it('lưu sản phẩm: dòng read model và facet danh mục gồm danh mục cha; đổi trạng thái cập nhật ngay', function () {
    expect(($this->facets)('category'))->toBe(["category={$this->women->id}", "category={$this->dresses->id}"])
        ->and(($this->listing)()->status)->toBe('active');

    ($this->as)(fn () => app(ProductService::class)->changeStatus($this->product->id, ProductStatus::Archived));

    expect(($this->listing)()->status)->toBe('archived');
});

it('chuyển danh mục sang cây khác: facet theo danh mục cha mới', function () {
    ($this->as)(fn () => app(CategoryService::class)->update($this->dresses, new CategoryInput('dam', CategoryStatus::Active, ['vi' => ['name' => 'Đầm']], parentId: $this->men->id), (int) $this->dresses->fresh()->lock_version));

    expect(($this->facets)('category'))->toBe(["category={$this->dresses->id}", "category={$this->men->id}"]);
});

it('bộ sưu tập: thêm và bỏ sản phẩm cập nhật facet', function () {
    $collection = ($this->as)(fn () => app(CollectionService::class)->save(['slug' => 'he', 'status' => 'active', 'position' => 0, 'translations' => ['vi' => ['name' => 'Hè']]], ['DL01']));
    expect(($this->facets)('collection'))->toBe(["collection={$collection->id}"]);

    ($this->as)(fn () => app(CollectionService::class)->save(['slug' => 'he', 'status' => 'active', 'position' => 0, 'translations' => ['vi' => ['name' => 'Hè']]], [], $collection->fresh()));

    expect(($this->facets)('collection'))->toBe([]);
});

it('tuỳ chọn: mã lọc của SKU đang bán; đổi kiểu hiển thị của loại ghi lại mã lọc', function () {
    [$variant] = P::variants($this->product, ['S']);
    $color = $variant->valueOf('color');
    DB::table('option_values')->where('id', $color->id)->update(['family' => 'black', 'filter_token' => 'black']);
    app(ProductListingProjector::class)->project($this->product->id);
    expect(($this->facets)('option:color'))->toBe(['option:color=black']);

    $type = OptionType::query()->where('code', 'color')->sole();
    ($this->as)(fn () => app(OptionTypeService::class)->saveType(['code' => 'color', 'display_type' => 'button', 'is_filterable' => true, 'position' => 0, 'translations' => ['vi' => ['name' => 'Màu']]], $type));

    expect(($this->facets)('option:color'))->toBe(["option:color={$color->code}"]);
});

it('xoá thuộc tính bỏ facet của nó', function () {
    $attribute = T::seed(fn () => Attribute::factory()->create(['input_type' => 'select', 'is_filterable' => true]));
    $value = T::seed(fn () => $attribute->values()->create(['code' => 'lua']));
    T::seed(fn () => $this->product->attributeValues()->create(['attribute_id' => $attribute->id, 'attribute_value_id' => $value->id]));
    expect(($this->facets)('attribute'))->toBe(["attribute:{$attribute->id}={$value->id}"]);

    ($this->as)(fn () => app(TaxonomyService::class)->deleteAttribute($attribute));

    expect(($this->facets)('attribute'))->toBe([]);
});

it('giá niêm yết (Pricing) và còn hàng (Inventory) ghi vào read model; dựng lại giữ nguyên hai cột này', function () {
    [$variant] = P::variants($this->product, ['S']);
    app(ListPrices::class)->setMinimums([$this->product->id => 250_000]);
    event(new AvailabilityChanged([$variant->id])); // chưa có tồn → hết hàng

    expect(($this->listing)())->toMatchObject(['list_price_min' => 250_000, 'in_stock' => 0]);

    DB::table('product_listing')->where('product_id', $this->product->id)->update(['status' => 'x']);
    $this->artisan('vani:catalog:rebuild-listing')->assertSuccessful();

    expect(($this->listing)())->toMatchObject(['status' => 'active', 'list_price_min' => 250_000]);
});

it('xoá sản phẩm nháp xoá dòng read model', function () {
    ($this->as)(fn () => app(ProductService::class)->changeStatus($this->product->id, ProductStatus::Draft));
    ($this->as)(fn () => app(ProductService::class)->deleteDraft($this->product->fresh()));

    expect(DB::table('product_listing')->count())->toBe(0)->and(DB::table('product_facet_values')->count())->toBe(0);
});

it('số đếm và facet của trang danh sách được cache, hết hiệu lực ngay khi read model đổi (db-refactor L56)', function () {
    $search = fn (): ProductSearchResult => app(ProductSearch::class)->search(new ProductSearchQuery(brandIds: [], now: now()->getTimestamp(), categoryId: $this->women->id));
    expect($search()->total)->toBe(1);

    $queries = 0;
    DB::listen(function () use (&$queries): void {
        $queries++;
    });
    $search();
    expect($queries)->toBeLessThanOrEqual(2); // chỉ trang id; số đếm + facet lấy từ cache

    ($this->as)(fn () => app(ProductService::class)->changeStatus($this->product->id, ProductStatus::Archived));

    expect($search()->total)->toBe(0);
});
