<?php

use Illuminate\Support\HtmlString;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Storefront\Application\Builder\Surfaces\BrandsSurface;
use Modules\Storefront\Application\Builder\Surfaces\CartSurface;
use Modules\Storefront\Application\Builder\Surfaces\HomeSurface;
use Modules\Storefront\Application\Builder\Surfaces\ListingSurface;
use Modules\Storefront\Application\Builder\Surfaces\ProductPageSurface;
use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\BuilderZone;
use Modules\Storefront\Testing\BuilderSurfaceContract;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/** Surface kiểu plugin: gắn vào slot có sẵn của Core và thử gắn vào slot không nhận block. */
final class BuilderEngineTestSurface implements BuilderSurface
{
    public function key(): string
    {
        return 'plugin-promo';
    }

    public function label(): string
    {
        return 'Khuyến mãi plugin';
    }

    public function zones(): array
    {
        return [
            BuilderZone::slot('vani.storefront.brands.after_list', 'Cuối trang thương hiệu', types: ['rich_text']),
            BuilderZone::slot('vani.storefront.layout.head', 'Trong head'),
        ];
    }
}

BuilderSurfaceContract::define('core home', fn () => new HomeSurface);
BuilderSurfaceContract::define('core product', fn () => new ProductPageSurface);
BuilderSurfaceContract::define('core listing', fn () => new ListingSurface);
BuilderSurfaceContract::define('core cart', fn () => new CartSurface);
BuilderSurfaceContract::define('core brands', fn () => new BrandsSurface);

beforeEach(function () {
    ['s' => $this->sku, 'brand' => $this->brand] = C::store();
    app(Extensions::class)->tag([BuilderEngineTestSurface::class], BuilderSurface::TAG);
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'storefront.manage'])->create(), 'staff');
});

it('đặt block vào slot có sẵn của trang sản phẩm, render sau nội dung plugin và giữ giới hạn vùng', function () {
    app(HookManager::class)->onSlot('vani.storefront.pdp.after_details', fn () => new HtmlString('<p>PLUGIN-PDP</p>'));
    $url = route('admin.storefront.builder.edit', 'product');

    $this->get($url)->assertInertia(fn (Assert $page) => $page->component('Storefront::Design/Builder')
        ->where('surface.key', 'product')
        ->where('surfaces', fn ($surfaces) => collect($surfaces)->pluck('key')->intersect(['home', 'product', 'listing', 'plugin-promo'])->count() === 4)
        ->where('zones.0.slot', 'vani.storefront.pdp.after_details')
        ->where('configured', false));

    $this->put($url, ['blocks' => [['type' => 'product_grid', 'zone' => 'vani.storefront.pdp.after_details', 'config' => ['title' => 'x', 'source' => 'newest', 'limit' => 4]]]])
        ->assertSessionHasErrors('blocks.0.zone');
    $this->put($url, ['blocks' => [['type' => 'rich_text', 'zone' => 'vani.storefront.khong_co', 'config' => ['body' => 'x']]]])
        ->assertSessionHasErrors('blocks.0.zone');
    $this->put($url, ['blocks' => [
        ['type' => 'rich_text', 'zone' => 'vani.storefront.pdp.after_details', 'config' => ['body' => '<p>Đổi trả trong 30 ngày</p><script>alert(1)</script>']],
        ['type' => 'rich_text', 'zone' => 'vani.storefront.pdp.after_related_products', 'config' => ['body' => '<p>Cuối trang sản phẩm</p>']],
    ]])->assertSessionHasNoErrors();

    $this->get(route('storefront.product', $this->sku->style->slug))->assertOk()->assertSeeInOrder([
        'data-slot="vani.storefront.pdp.after_details"', 'PLUGIN-PDP', 'Đổi trả trong 30 ngày',
        'data-slot="vani.storefront.pdp.after_related_products"', 'Cuối trang sản phẩm',
    ], false);
    $api = '/api/storefront/v1/pages/layouts/product?slug='.$this->sku->style->slug;
    $layout = $this->getJson($api)->assertOk()->assertHeader('Cache-Control', 'no-store, private')
        ->assertJsonPath('meta', ['surface' => 'product', 'configured' => true])
        ->assertJsonPath('data.0.key', 'vani.storefront.pdp.after_details')
        ->assertJsonPath('data.1.key', 'vani.storefront.pdp.after_related_products')
        ->assertJsonPath('data.0.blocks.0.type', 'rich_text')->json();
    expect($layout['data'][0]['blocks'][0]['data']['bodyHtml'])->toBeString()
        ->toContain('Đổi trả trong 30 ngày')->not->toContain('<script>', 'PLUGIN-PDP');

    $this->delete($url)->assertSessionHasNoErrors();
    $this->get(route('storefront.product', $this->sku->style->slug))->assertOk()->assertDontSee('Đổi trả trong 30 ngày');
    $this->getJson($api)->assertOk()->assertJsonPath('meta.configured', false)->assertJsonPath('data.0.blocks', []);
});

it('surface của plugin dùng slot Core khai báo builder; vùng gắn vào slot không nhận block bị bỏ', function () {
    $url = route('admin.storefront.builder.edit', 'plugin-promo');
    $this->get($url)->assertInertia(fn (Assert $page) => $page->where('zones', [])->where('surface.label', 'Khuyến mãi plugin'));

    $this->put($url, ['blocks' => [['type' => 'rich_text', 'config' => ['body' => '<p>Mã giảm giá thương hiệu</p>']]]])->assertSessionHasNoErrors();

    $html = $this->get(route('storefront.brands'))->assertOk()
        ->assertSeeInOrder(['data-slot="vani.storefront.brands.after_list"', 'Mã giảm giá thương hiệu'], false)->getContent();
    expect(substr($html, 0, (int) strpos($html, '</head>')))->not->toContain('Mã giảm giá thương hiệu');
    $this->get(route('admin.storefront.builder.edit', 'khong-co'))->assertNotFound();
    $this->getJson('/api/storefront/v1/pages/layouts/brands')->assertOk()
        ->assertJsonPath('meta.configured', true)->assertJsonCount(2, 'data')
        ->assertJsonPath('data.1.blocks.0.data.bodyHtml', '&lt;p&gt;Mã giảm giá thương hiệu&lt;/p&gt;');
    $this->getJson('/api/storefront/v1/pages/layouts/plugin-promo')->assertNotFound();
});

it('khối theo ngữ cảnh dùng phạm vi thương hiệu đang xem và chỉ xuất hiện trong vùng hỗ trợ', function () {
    $title = 'Sản phẩm theo thương hiệu đang xem';
    $block = ['type' => 'listing_product_grid', 'zone' => 'vani.storefront.plp.after_listing', 'config' => ['title' => $title]];
    $url = route('admin.storefront.builder.edit', 'listing');
    $this->get($url)->assertInertia(fn (Assert $page) => $page->has('allowedZones.listing_product_grid', 3));
    $this->put($url, ['blocks' => [$block]])->assertSessionHasNoErrors();
    $this->put(route('admin.storefront.builder.edit', 'home'), ['blocks' => [$block]])->assertSessionHasErrors('blocks.0.zone');

    $this->get(route('storefront.brand', $this->brand->slug))->assertSeeInOrder([
        'data-slot="vani.storefront.plp.after_listing"', $title, 'Đầm lụa',
    ], false);
    $this->get(route('storefront.search'))->assertDontSee($title);
});

it('API dựng lưới theo slug công khai của danh mục, bộ sưu tập và thương hiệu, đồng thời giữ chính sách ẩn giá', function () {
    $style = $this->sku->style;
    T::seed(function () use ($style): void {
        $category = Category::factory()->create(['slug' => 'dam-lua']);
        $category->syncTranslations(['vi' => ['name' => 'Đầm lụa']]);
        $collection = ProductCollection::query()->create(['slug' => 'mua-he', 'status' => 'active']);
        $collection->syncTranslations(['vi' => ['name' => 'Mùa hè'], 'en' => ['name' => 'Summer', 'slug' => 'summer']]);
        $style->categories()->attach($category);
        $collection->styles()->attach($style);
    });
    $this->put(route('admin.storefront.builder.edit', 'listing'), ['blocks' => [
        ['type' => 'listing_product_grid', 'zone' => 'vani.storefront.plp.after_listing', 'config' => ['title' => 'Gợi ý']],
    ]])->assertSessionHasNoErrors();

    $api = '/api/storefront/v1/pages/layouts/listing';
    foreach (['category' => 'dam-lua', 'collection' => 'mua-he', 'brand' => $this->brand->slug] as $type => $slug) {
        $this->getJson("{$api}?type={$type}&slug={$slug}")->assertOk()
            ->assertJsonPath('data.1.key', 'vani.storefront.plp.after_listing')
            ->assertJsonPath('data.1.blocks.0.data.products.0.id', $style->id);
    }
    $this->getJson("{$api}?type=collection&slug=summer", ['X-Vani-Locale' => 'en'])->assertOk()
        ->assertJsonPath('data.1.blocks.0.data.products.0.id', $style->id);
    $this->getJson("{$api}?type=search")->assertOk()->assertJsonPath('data.1.blocks', []);
    $this->getJson("{$api}?type=search&slug=dam-lua")->assertUnprocessable();
    $this->getJson("{$api}?type=brand")->assertUnprocessable();
    $this->getJson("{$api}?type=brand&slug=khong-co")->assertNotFound();

    T::seed(fn () => app(Settings::class)->set('core', 'storefront.price_visibility', 'members'));
    $this->getJson("{$api}?type=brand&slug={$this->brand->slug}")->assertOk()
        ->assertJsonPath('data.1.blocks.0.data.products.0.price', null)
        ->assertJsonPath('data.1.blocks.0.data.products.0.price_hidden', true);
    T::seed(fn () => $style->update(['status' => 'draft']));
    $this->getJson('/api/storefront/v1/pages/layouts/product?slug='.$style->slug)->assertNotFound();
});

it('API giỏ phân biệt bố cục chưa lưu với cấu hình lưu rỗng', function () {
    $api = '/api/storefront/v1/pages/layouts/cart';
    $this->getJson($api)->assertOk()->assertJsonPath('meta.configured', false)
        ->assertJsonPath('data', [['key' => 'vani.storefront.cart.empty', 'blocks' => []]]);
    $this->put(route('admin.storefront.builder.edit', 'cart'), ['blocks' => []])->assertSessionHasNoErrors();
    $this->getJson($api)->assertOk()->assertJsonPath('meta.configured', true)->assertJsonPath('data.0.blocks', []);
});

it('API và Blade giữ cùng thứ tự block Core và plugin khi dùng chung slot', function () {
    $slot = 'vani.storefront.brands.after_list';
    $coreEditor = route('admin.storefront.builder.edit', 'brands');
    $pluginEditor = route('admin.storefront.builder.edit', 'plugin-promo');
    $this->put($coreEditor, ['blocks' => [
        ['type' => 'rich_text', 'zone' => $slot, 'config' => ['body' => 'Nội dung Core thứ nhất']],
        ['type' => 'rich_text', 'zone' => $slot, 'config' => ['body' => 'Nội dung Core thứ hai']],
    ]])->assertSessionHasNoErrors();
    $this->put($pluginEditor, ['blocks' => [
        ['type' => 'rich_text', 'config' => ['body' => 'Nội dung plugin']],
    ]])->assertSessionHasNoErrors();

    $api = '/api/storefront/v1/pages/layouts/brands';
    $storefront = route('storefront.brands');
    $blocks = $this->getJson($api)->assertOk()->assertJsonPath('data.0.blocks', [])->json('data.1.blocks');
    $ordered = ['Nội dung Core thứ nhất', 'Nội dung Core thứ hai', 'Nội dung plugin'];
    expect(array_column(array_column($blocks, 'data'), 'bodyHtml'))->toBe($ordered);
    $this->get($storefront)->assertOk()->assertSeeInOrder($ordered);

    $this->delete($pluginEditor)->assertSessionHasNoErrors();
    $this->getJson($api)->assertOk()->assertJsonPath('meta.configured', true)->assertJsonCount(2, 'data.1.blocks');
    $this->get($storefront)->assertOk()->assertDontSee('Nội dung plugin');
});

it('xem trước block nháp bằng theme và nhập bố cục vào vùng phù hợp trước khi lưu', function () {
    $block = ['type' => 'rich_text', 'config' => ['body' => '<p>Nội dung nháp độc lập</p>']];
    $preview = route('admin.storefront.builder.draft', ['home', 'preview']);
    $html = $this->postJson($preview, ['blocks' => [$block]])->assertOk()->assertHeader('Cache-Control', 'no-store, private')->json('html');
    expect($html)->toContain('name="viewport"', 'Nội dung nháp độc lập');
    $this->get(route('storefront.home'))->assertDontSee('Nội dung nháp độc lập');

    $import = route('admin.storefront.builder.draft', ['product', 'import']);
    $this->postJson($import, ['version' => 1, 'blocks' => [[...$block, 'zone' => 'main']]])
        ->assertOk()->assertJsonPath('blocks.0.zone', 'vani.storefront.pdp.after_details');
    $this->postJson($import, ['version' => 2, 'blocks' => [$block]])->assertUnprocessable()->assertJsonValidationErrors('version');
    $this->postJson($import, ['version' => 1, 'blocks' => [['type' => 'listing_product_grid']]])
        ->assertUnprocessable()->assertJsonValidationErrors('blocks.0.zone');
    $this->get(route('admin.storefront.builder.edit', 'product'))->assertInertia(fn (Assert $page) => $page->where('configured', false));
});

it('yêu cầu quyền quản lý giao diện khi xem trước hoặc nhập bản nháp', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff');
    foreach (['preview', 'import'] as $operation) {
        $this->postJson(route('admin.storefront.builder.draft', ['home', $operation]), ['blocks' => []])->assertForbidden();
    }
});
