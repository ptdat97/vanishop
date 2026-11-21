<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Plugin\Lookbook\Infrastructure\Lookbooks;
use Plugin\Lookbook\LookbookServiceProvider;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

it('Admin tìm và gắn sản phẩm vào ảnh, khách mở trang sản phẩm bằng bàn phím', function () {
    Storage::fake('public');
    ['s' => $variant] = C::store();
    $product = $variant->style;
    $lookbook = T::seed(function () {
        app(PluginManager::class)->install(LookbookServiceProvider::ID);
        app(PluginManager::class)->enable(LookbookServiceProvider::ID);
        app()->register(LookbookServiceProvider::class);
        $photo = app(MediaLibrary::class)->store(UploadedFile::fake()->image('look.jpg', 600, 900));

        return app(Lookbooks::class)->save(null, [
            'translations' => ['vi' => ['name' => 'Phối đồ']],
            'images' => [['media_id' => $photo->id]],
            'active' => true,
        ]);
    });
    $this->actingAs(T::staff(['admin.access', 'lookbook.view', 'lookbook.manage', 'media.view']), 'staff');

    $admin = visit("/admin/plugins/vani-lookbook/{$lookbook->id}")
        ->type('input[aria-label="Thêm sản phẩm vào ảnh 1"]', $product->style_code)
        ->click('[role="option"] >> text='.$product->style_code)
        ->press('Lưu')
        ->waitForText('Đã lưu')
        ->assertNoJavaScriptErrors();
    expect($lookbook->fresh()->images->sole()->product_ids)->toBe([$product->id]);

    $marker = '[data-lookbook-product-markers] a';
    $admin->navigate('/lookbook/'.$lookbook->slug)
        ->assertSeeIn('[data-lookbook-products]', 'Đầm lụa')
        ->keys($marker, 'Enter')
        ->assertPathIs('/san-pham/'.$product->slug)
        ->assertNoJavaScriptErrors();
});
