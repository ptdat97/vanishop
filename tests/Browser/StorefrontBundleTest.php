<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as Catalog;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as Checkout;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as Inventory;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as Pricing;
use Plugin\Bundles\Application\Bundles;
use Plugin\Bundles\BundlesServiceProvider;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

it('chọn SKU, thêm cả combo đúng một lần và thấy mức giảm trong giỏ mini', function (string $routeName) {
    ['s' => $shirt, 'location' => $warehouse] = Checkout::store();
    [$skirt] = Pricing::variants(Catalog::product(null, ['name' => 'Chân váy']), ['M']);
    Pricing::priceList([], [$skirt->id => [150_000]]);
    Inventory::stock($warehouse, $skirt->id, 5);
    Catalog::seed(function () use ($shirt, $skirt) {
        $plugins = app(PluginManager::class);
        $plugins->install(BundlesServiceProvider::ID);
        $plugins->enable(BundlesServiceProvider::ID);
        app()->register(BundlesServiceProvider::class);
        app(Bundles::class)->save([
            'slug' => 'bo-cong-so', 'name' => 'Bộ công sở', 'description' => null, 'image_media_id' => null,
            'discount_percent' => 10, 'active' => true, 'starts_at' => null, 'ends_at' => null, 'position' => 0,
            'items' => [['product_id' => $shirt->product_id, 'quantity' => 2], ['product_id' => $skirt->product_id, 'quantity' => 1]],
        ]);
    });

    $params = $routeName === 'storefront.product' ? [$shirt->product->slug] : [];
    $page = visit(route($routeName, $params, absolute: false));
    $page->assertVisible('[data-vani-bundle-form]')
        ->select('[data-vani-bundle-form] select[name="items[0][variant_id]"]', (string) $shirt->id);
    $page->script(<<<'JS'
        () => {
            window.bundleAdds = [];
            document.addEventListener('vani:cart-updated', ({ detail }) => {
                if (detail.action === 'add') window.bundleAdds.push([detail.variant_id, detail.quantity]);
            });
            const form = document.querySelector('[data-vani-bundle-form]');
            form.requestSubmit();
            form.requestSubmit();
        }
    JS);

    $page->assertVisible('[data-vani-offcanvas-cart][open]')
        ->assertSeeIn('[data-vani-mini-cart-body]', 'Chân váy')
        ->assertSeeIn('[data-vani-mini-cart-body]', '−75.000 ₫')
        ->assertSeeIn('header [data-vani-cart-count]', '3')
        ->assertScript('window.bundleAdds', [[$shirt->id, 2], [$skirt->id, 1]])
        ->assertNoJavaScriptErrors();
})->with([
    'trang sản phẩm' => ['storefront.product'],
    'trang Combo' => ['storefront.p.vani-bundles.index'],
]);
