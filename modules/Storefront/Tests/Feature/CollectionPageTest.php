<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

it('trang bộ sưu tập: phần đầu mặc định (tên, mô tả, ảnh bìa, SEO), giữ phạm vi sản phẩm theo URL, sitemap và API', function () {
    Storage::fake('public');
    ['s' => $sku, 'location' => $location] = C::store();
    $other = T::product(null, ['name' => 'Sản phẩm ngoài bộ sưu tập']);
    [$otherSku] = P::variants($other, ['S']);
    P::priceList([], [$otherSku->id => [450_000]]);
    I::stock($location, $otherSku->id, 10);
    $collection = T::seed(function () use ($sku): ProductCollection {
        $media = app(MediaLibrary::class)->store(UploadedFile::fake()->image('bia.jpg', 1600, 900));
        $collection = ProductCollection::query()->create(['slug' => 'mua-he', 'status' => 'active', 'image_media_id' => $media->id]);
        $collection->syncTranslations([
            'vi' => ['name' => 'Mùa hè', 'description' => 'Vải mát cho ngày nắng.', 'meta_title' => 'Mùa hè rực rỡ'],
            'en' => ['name' => 'Summer', 'slug' => 'summer'],
        ]);
        $collection->styles()->attach($sku->style_id);

        return $collection;
    });

    $this->get('/tim-kiem')->assertOk()->assertSee('data-vani-product="'.$other->slug.'"', false);
    $this->get('/bo-suu-tap/mua-he')->assertOk()
        ->assertSee('<title>Mùa hè rực rỡ', false)
        ->assertSeeInOrder(['data-collection-banner', 'Mùa hè', 'Vải mát cho ngày nắng.', '-w', 'data-vani-listing', 'data-vani-product="'.$sku->style->slug.'"'], false)
        ->assertDontSee('data-vani-product="'.$other->slug.'"', false);
    $this->get('/bo-suu-tap/mua-he?collection=khong-co')->assertOk()->assertSee('data-vani-product="'.$sku->style->slug.'"', false);
    $this->get('/en/collections/mua-he?sort=price_asc')->assertRedirect('/en/collections/summer?sort=price_asc')->assertStatus(301);
    $this->get('/en/collections/summer')->assertOk()->assertSee('Summer');
    $this->get('/sitemap.xml')->assertOk()->assertSee('/bo-suu-tap/mua-he')->assertSee('/en/collections/summer');
    $this->getJson('/api/storefront/v1/collections/summer', ['X-Vani-Locale' => 'en'])->assertOk()
        ->assertJsonPath('data.id', $collection->id)->assertJsonPath('data.name', 'Summer')->assertJsonMissingPath('data.blocks');

    T::seed(fn () => $collection->update(['status' => 'hidden']));
    $this->get('/bo-suu-tap/mua-he')->assertNotFound();
    $this->getJson('/api/storefront/v1/collections/mua-he')->assertNotFound();
});
