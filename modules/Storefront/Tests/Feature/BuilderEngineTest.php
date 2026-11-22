<?php

use Illuminate\Support\HtmlString;
use Inertia\Testing\AssertableInertia as Assert;
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
    ['s' => $this->sku] = C::store();
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
        ['type' => 'rich_text', 'zone' => 'vani.storefront.pdp.after_details', 'config' => ['body' => '<p>Đổi trả trong 30 ngày</p>']],
        ['type' => 'rich_text', 'zone' => 'vani.storefront.pdp.after_related_products', 'config' => ['body' => '<p>Cuối trang sản phẩm</p>']],
    ]])->assertSessionHasNoErrors();

    $this->get(route('storefront.product', $this->sku->style->slug))->assertOk()->assertSeeInOrder([
        'data-slot="vani.storefront.pdp.after_details"', 'PLUGIN-PDP', 'Đổi trả trong 30 ngày',
        'data-slot="vani.storefront.pdp.after_related_products"', 'Cuối trang sản phẩm',
    ], false);

    $this->delete($url)->assertSessionHasNoErrors();
    $this->get(route('storefront.product', $this->sku->style->slug))->assertOk()->assertDontSee('Đổi trả trong 30 ngày');
});

it('surface của plugin dùng slot Core khai báo builder; vùng gắn vào slot không nhận block bị bỏ', function () {
    $url = route('admin.storefront.builder.edit', 'plugin-promo');
    $this->get($url)->assertInertia(fn (Assert $page) => $page->where('zones', [])->where('surface.label', 'Khuyến mãi plugin'));

    $this->put($url, ['blocks' => [['type' => 'rich_text', 'config' => ['body' => '<p>Mã giảm giá thương hiệu</p>']]]])->assertSessionHasNoErrors();

    $html = $this->get(route('storefront.brands'))->assertOk()
        ->assertSeeInOrder(['data-slot="vani.storefront.brands.after_list"', 'Mã giảm giá thương hiệu'], false)->getContent();
    expect(substr($html, 0, (int) strpos($html, '</head>')))->not->toContain('Mã giảm giá thương hiệu');
    $this->get(route('admin.storefront.builder.edit', 'khong-co'))->assertNotFound();
});
