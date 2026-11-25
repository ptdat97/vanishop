<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as Catalog;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as Checkout;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Storefront\Testing\BuilderSurfaceContract;
use Plugin\CatalogPages\CatalogPagesServiceProvider;
use Plugin\CatalogPages\Infrastructure\CatalogPageSurface;

require_once dirname(__DIR__, 5).'/modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

BuilderSurfaceContract::define('vani.catalog-pages', fn () => app(CatalogPageSurface::class));

beforeEach(function () {
    Catalog::seed(function () {
        app(PluginManager::class)->install(CatalogPagesServiceProvider::ID);
        app(PluginManager::class)->enable(CatalogPagesServiceProvider::ID);
    });
    app()->register(CatalogPagesServiceProvider::class);
    app(PluginActivation::class)->flush();
    ['s' => $this->sku, 'brand' => $this->brand] = Checkout::store();
    [$this->category, $this->collection] = Catalog::seed(function (): array {
        $category = Category::factory()->create(['slug' => 'dam-lua']);
        $category->syncTranslations(['vi' => ['name' => 'Đầm lụa']]);
        $this->sku->style->categories()->attach($category);
        $collection = ProductCollection::query()->create(['slug' => 'mua-he', 'status' => 'active']);
        $collection->syncTranslations(['vi' => ['name' => 'Mùa hè']]);
        $collection->styles()->attach($this->sku->style_id);

        return [$category, $collection];
    });
    $this->actingAs(Catalog::staff(['admin.access', 'catalog.view', 'catalog.manage', 'storefront.manage']), 'staff');
});

it('nút "Nội dung trang" ở trang sửa danh mục, thương hiệu, bộ sưu tập mở trình biên tập của đúng đối tượng; cần quyền storefront.manage', function () {
    $pages = [
        'category' => [route('admin.catalog.categories.edit', $this->category->id), "category-{$this->category->id}", 'Danh mục Đầm lụa'],
        'brand' => [route('admin.catalog.brands.edit', $this->brand->id), "brand-{$this->brand->id}", 'Thương hiệu '.$this->brand->name],
        'collection' => [route('admin.catalog.collections.edit', $this->collection->id), "collection-{$this->collection->id}", 'Bộ sưu tập Mùa hè'],
    ];
    foreach ($pages as [$form, $scope, $label]) {
        $editor = route('admin.storefront.builder.scope.edit', ['catalog-pages', $scope]);
        $this->get($form)->assertInertia(fn (Assert $page) => $page->where('extensionLinks.0.url', $editor)->where('extensionLinks.0.label', 'Nội dung trang'));
        $this->get($editor)->assertInertia(fn (Assert $page) => $page->component('Storefront::Design/Builder')
            ->where('scope.label', $label)->where('scope.backUrl', $form)->where('surface.key', 'catalog-pages'));
    }
    $this->get(route('admin.storefront.builder.scope.edit', ['catalog-pages', 'brand-999999']))->assertNotFound();
    $this->get(route('admin.storefront.builder.scope.edit', ['catalog-pages', 'product-1']))->assertNotFound();

    $this->actingAs(Catalog::staff(['admin.access', 'catalog.view', 'catalog.manage']), 'staff');
    $this->get($pages['brand'][0])->assertInertia(fn (Assert $page) => $page->where('extensionLinks', []));
});

it('nội dung riêng thay nội dung chung theo vùng, chỉ trên trang của đúng đối tượng; thương hiệu ẩn vẫn sửa được nhưng không có link xem trang', function () {
    $this->put(route('admin.storefront.builder.edit', 'listing'), ['blocks' => [
        ['type' => 'rich_text', 'zone' => 'vani.storefront.plp.before_listing', 'config' => ['body' => '<p>Ưu đãi chung</p>']],
    ]])->assertSessionHasNoErrors();
    $this->put(route('admin.storefront.builder.scope.update', ['catalog-pages', "category-{$this->category->id}"]), ['blocks' => [
        ['type' => 'hero', 'zone' => 'vani.storefront.plp.header', 'config' => ['title' => 'Lụa mát mùa hè']],
        ['type' => 'rich_text', 'zone' => 'vani.storefront.plp.before_listing', 'config' => ['body' => '<p>Cách chọn lụa</p>']],
    ]])->assertSessionHasNoErrors();

    $this->get('/danh-muc/dam-lua')->assertOk()
        ->assertSeeInOrder(['<h1 id="listing-title"', 'Lụa mát mùa hè', 'Cách chọn lụa', 'data-vani-product="'.$this->sku->style->slug.'"'], false)
        ->assertDontSee('Ưu đãi chung');
    $this->get('/bo-suu-tap/mua-he')->assertOk()->assertSeeInOrder(['Mùa hè', 'Ưu đãi chung'], false)->assertDontSee('Cách chọn lụa');
    $this->get('/thuong-hieu/'.$this->brand->slug)->assertOk()->assertSee('Ưu đãi chung')->assertDontSee('Lụa mát mùa hè');

    Catalog::seed(fn () => Brand::query()->whereKey($this->brand->id)->update(['status' => Brand::HIDDEN]));
    $this->get(route('admin.storefront.builder.scope.edit', ['catalog-pages', "brand-{$this->brand->id}"]))
        ->assertInertia(fn (Assert $page) => $page->where('previewUrl', null));

    // Tắt plugin: trang dùng lại nội dung chung, nút trên trang sửa biến mất.
    Catalog::seed(fn () => app(PluginManager::class)->disable(CatalogPagesServiceProvider::ID));
    app(PluginActivation::class)->flush();
    app()->forgetScopedInstances();
    $this->get('/danh-muc/dam-lua')->assertOk()->assertSee('Ưu đãi chung')->assertDontSee('Cách chọn lụa');
    $this->get(route('admin.catalog.categories.edit', $this->category->id))->assertInertia(fn (Assert $page) => $page->where('extensionLinks', []));
});
