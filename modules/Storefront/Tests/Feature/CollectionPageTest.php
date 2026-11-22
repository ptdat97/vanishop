<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\View\View as BladeView;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Contracts\CollectionPages;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

it('biên tập banner và các vùng nội dung, render đúng thứ tự và giữ phạm vi sản phẩm theo URL', function () {
    Storage::fake('public');
    ['s' => $sku, 'location' => $location] = C::store();
    $other = T::product(null, ['name' => 'Sản phẩm ngoài bộ sưu tập']);
    [$otherSku] = P::variants($other, ['S']);
    P::priceList([], [$otherSku->id => [450_000]]);
    I::stock($location, $otherSku->id, 10);
    [$collection, $media] = T::seed(function () use ($sku): array {
        $collection = ProductCollection::query()->create(['slug' => 'mua-he', 'status' => 'active']);
        $collection->syncTranslations([
            'vi' => ['name' => 'Mùa hè'],
            'en' => ['name' => 'Summer', 'slug' => 'summer'],
        ]);
        $collection->styles()->attach($sku->style_id);

        return [$collection, app(MediaLibrary::class)->store(UploadedFile::fake()->image('banner.jpg', 1200, 800))];
    });
    $this->actingAs(T::staff(['admin.access', 'catalog.manage', 'storefront.manage']), 'staff');
    $editor = route('admin.storefront.collection-pages.edit', $collection->id);
    $this->get(route('admin.catalog.collections.edit', $collection->id))->assertInertia(fn (Assert $page) => $page->where('contentUrl', $editor));
    $this->get($editor)->assertInertia(fn (Assert $page) => $page->where('collectionName', 'Mùa hè')->where('blocks', []));
    $this->put($editor, ['blocks' => [['type' => 'rich_text', 'zone' => 'banner', 'config' => ['body' => 'Sai loại']]]])->assertSessionHasErrors('blocks.0.zone');
    $blocks = [
        ['type' => 'rich_text', 'zone' => 'after', 'config' => ['body' => '<p>Khung sau lưới</p>']],
        ['type' => 'hero', 'zone' => 'banner', 'config' => ['title' => 'Banner mùa hè', 'image' => $media->id]],
        ['type' => 'rich_text', 'zone' => 'before', 'config' => ['body' => '<p>Khung trước lưới</p>']],
        ['type' => 'rich_text', 'zone' => 'before', 'config' => ['body' => '<p>Giới thiệu chất liệu</p>']],
    ];
    $this->put($editor, ['blocks' => [$blocks[1], $blocks[1]]])->assertSessionHasErrors('blocks.1.zone');
    $this->put($editor, ['blocks' => $blocks])->assertSessionHasNoErrors();
    expect(DB::table('mediables')->where(['media_id' => $media->id, 'mediable_type' => CollectionPages::MEDIA_OWNER, 'mediable_id' => $collection->id])->exists())->toBeTrue();

    $this->get('/tim-kiem')->assertOk()->assertSee('data-vani-product="'.$other->slug.'"', false);
    $this->get('/bo-suu-tap/mua-he')->assertOk()->assertSeeInOrder([
        'data-collection-banner', 'Banner mùa hè', 'Khung trước lưới', 'Giới thiệu chất liệu', 'data-vani-listing', 'data-vani-product="'.$sku->style->slug.'"', 'Khung sau lưới',
    ], false)->assertDontSee('data-vani-product="'.$other->slug.'"', false);
    $this->get('/bo-suu-tap/mua-he?collection=khong-co')->assertOk()
        ->assertSee('data-vani-product="'.$sku->style->slug.'"', false);
    $this->get('/en/collections/mua-he?sort=price_asc')->assertRedirect('/en/collections/summer?sort=price_asc')->assertStatus(301);
    $this->get('/en/collections/summer')->assertOk()->assertSee('Summer');
    $this->get('/sitemap.xml')->assertOk()->assertSee('/bo-suu-tap/mua-he')->assertSee('/en/collections/summer');
    $this->getJson('/api/storefront/v1/collections/summer', ['X-Vani-Locale' => 'en'])->assertOk()
        ->assertJsonPath('data.blocks.banner.0.type', 'hero')
        ->assertJsonPath('data.blocks.before.0.type', 'rich_text')
        ->assertJsonCount(2, 'data.blocks.before')
        ->assertJsonPath('data.blocks.after.0.type', 'rich_text');
    $this->put($editor, ['blocks' => []])->assertSessionHasNoErrors();
    expect(DB::table('mediables')->where('media_id', $media->id)->exists())->toBeFalse();
    $this->get('/bo-suu-tap/mua-he')->assertOk()
        ->assertSeeInOrder(['data-collection-banner', 'Mùa hè', 'data-vani-listing'], false)
        ->assertDontSee('Khung trước lưới')->assertDontSee('Khung sau lưới');

    T::seed(fn () => $collection->update(['status' => 'hidden']));
    $this->get('/bo-suu-tap/mua-he')->assertNotFound();
    $this->getJson('/api/storefront/v1/collections/mua-he')->assertNotFound();
    $this->actingAs(T::staff(['admin.access', 'catalog.manage']), 'staff')
        ->put($editor, ['blocks' => $blocks])->assertForbidden();
});

it('trang chủ và trang bộ sưu tập dùng cùng schema biên tập và view Hero', function () {
    $collection = T::seed(fn () => ProductCollection::query()->create(['slug' => 'bo-chung', 'status' => 'active']));
    $this->actingAs(T::staff(['admin.access', 'storefront.manage']), 'staff');
    $home = route('admin.storefront.home-blocks.edit');
    $editor = route('admin.storefront.collection-pages.edit', $collection->id);
    $homeProps = $this->get($home)->inertiaPage()['props'];
    expect($homeProps['zones'])->toBe([]);

    $this->get($editor)->assertInertia(fn (Assert $page) => $page->component('Storefront::Design/HomeBlocks')
        ->where('types', $homeProps['types'])
        ->where('zones', [
            ['key' => 'banner', 'types' => ['hero'], 'max' => 1],
            ['key' => 'before', 'types' => null, 'max' => null],
            ['key' => 'after', 'types' => null, 'max' => null],
        ]));

    $invalid = ['type' => 'hero', 'config' => []];
    $this->put($home, ['blocks' => [$invalid]])->assertSessionHasErrors('blocks.0.config.title');
    $this->put($editor, ['blocks' => [$invalid + ['zone' => 'banner']]])->assertSessionHasErrors('blocks.0.config.title');

    $hero = ['type' => 'hero', 'config' => ['title' => 'Cùng một khối']];
    $this->put($home, ['blocks' => [$hero]])->assertSessionHasNoErrors();
    $this->put($editor, ['blocks' => [$hero + ['zone' => 'banner']]])->assertSessionHasNoErrors();
    View::composer('theme::blocks.hero', static function (BladeView $view): void {
        $view->with('title', $view->getData()['title'].' · Hiển thị từ view Hero');
    });

    $this->get('/')->assertSee('Cùng một khối · Hiển thị từ view Hero');
    $this->get('/bo-suu-tap/bo-chung')->assertSeeInOrder(['data-collection-banner', 'Cùng một khối · Hiển thị từ view Hero'], false);
});
