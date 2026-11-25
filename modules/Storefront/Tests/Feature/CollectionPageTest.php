<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Storefront\Application\Builder\BuilderEngine;
use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\BlockContext;
use Modules\Storefront\Contracts\Data\BuilderScope;
use Modules\Storefront\Contracts\Data\BuilderZone;
use Modules\Storefront\Contracts\ScopedBuilderSurface;
use Modules\Storefront\Testing\BuilderSurfaceContract;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/** Surface theo phạm vi kiểu plugin: nội dung riêng từng bộ sưu tập, gắn vào slot đầu trang và quanh lưới sản phẩm. */
final class CollectionScopeTestSurface implements ScopedBuilderSurface
{
    public function key(): string
    {
        return 'test-pages';
    }

    public function label(): string
    {
        return 'Trang riêng';
    }

    public function zones(): array
    {
        return [
            BuilderZone::slot('vani.storefront.plp.header', 'Đầu trang', max: 1),
            BuilderZone::slot('vani.storefront.plp.before_listing', 'Trên lưới'),
            BuilderZone::slot('vani.storefront.plp.after_listing', 'Dưới lưới'),
        ];
    }

    public function scopeOf(BlockContext $context): ?string
    {
        return $context->listingType === 'collection' && $context->listingId !== null ? "collection-{$context->listingId}" : null;
    }

    public function describeScope(string $scope): ?BuilderScope
    {
        $collection = preg_match('/^collection-(\d+)$/', $scope, $match) === 1 ? ProductCollection::query()->find((int) $match[1]) : null;

        return $collection === null ? null : new BuilderScope('Bộ sưu tập '.$collection->slug, backUrl: '/admin/catalog/collections', listingType: 'collection', listingSlug: $collection->slug);
    }
}

BuilderSurfaceContract::define('scoped test surface', fn () => new CollectionScopeTestSurface);

beforeEach(function () {
    app(Extensions::class)->tag([CollectionScopeTestSurface::class], BuilderSurface::TAG);
});

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

it('nội dung theo phạm vi: biên tập riêng từng bộ sưu tập, thay nội dung chung theo vùng, Hero ở đầu trang thành banner có h1', function () {
    Storage::fake('public');
    ['s' => $sku] = C::store();
    [$summer, , $media] = T::seed(function () use ($sku): array {
        $collections = [];
        foreach (['mua-he' => 'Mùa hè', 'mua-dong' => 'Mùa đông'] as $slug => $name) {
            $collection = ProductCollection::query()->create(['slug' => $slug, 'status' => 'active']);
            $collection->syncTranslations(['vi' => ['name' => $name]]);
            $collection->styles()->attach($sku->style_id);
            $collections[] = $collection;
        }

        return [...$collections, app(MediaLibrary::class)->store(UploadedFile::fake()->image('banner.jpg', 1200, 800))];
    });
    $this->actingAs(T::staff(['admin.access', 'catalog.manage', 'storefront.manage']), 'staff');
    $shared = route('admin.storefront.builder.edit', 'listing');
    $this->put($shared, ['blocks' => [
        ['type' => 'rich_text', 'zone' => 'vani.storefront.plp.before_listing', 'config' => ['body' => '<p>Ưu đãi chung</p>']],
        ['type' => 'rich_text', 'zone' => 'vani.storefront.plp.after_listing', 'config' => ['body' => '<p>Cam kết chung</p>']],
    ]])->assertSessionHasNoErrors();

    $editor = route('admin.storefront.builder.scope.edit', ['test-pages', "collection-{$summer->id}"]);
    $this->get(route('admin.storefront.builder.edit', 'test-pages'))->assertNotFound();
    $this->get(route('admin.storefront.builder.scope.edit', ['test-pages', 'collection-999999']))->assertNotFound();
    $this->get($shared)->assertInertia(fn (Assert $page) => $page->where('surfaces', fn ($surfaces) => ! collect($surfaces)->contains('key', 'test-pages')));
    $this->get($editor)->assertInertia(fn (Assert $page) => $page->component('Storefront::Design/Builder')
        ->where('scope.label', 'Bộ sưu tập mua-he')->where('configured', false)->where('blocks', []));

    $hero = ['type' => 'hero', 'zone' => 'vani.storefront.plp.header', 'config' => ['title' => 'Banner mùa hè', 'image' => $media->id]];
    $this->put($editor, ['blocks' => [$hero, $hero]])->assertSessionHasErrors('blocks.1.zone');
    $this->put($editor, ['blocks' => [
        $hero,
        ['type' => 'rich_text', 'zone' => 'vani.storefront.plp.before_listing', 'config' => ['body' => '<p>Chất liệu mùa hè</p>']],
    ]])->assertSessionHasNoErrors();
    expect(app(BuilderEngine::class)->configuredScopes('test-pages'))->toBe(["collection-{$summer->id}"])
        ->and(DB::table('mediables')->where(['media_id' => $media->id, 'mediable_type' => BuilderEngine::SCOPED_MEDIA_OWNER])->exists())->toBeTrue();

    // Vùng có nội dung riêng thay nội dung chung; vùng để trống vẫn hiện nội dung chung. Hero ở đầu trang là banner có h1.
    $this->get('/bo-suu-tap/mua-he')->assertOk()
        ->assertSeeInOrder(['<h1 id="listing-title"', 'Banner mùa hè', 'Chất liệu mùa hè', 'data-vani-product="'.$sku->style->slug.'"', 'Cam kết chung'], false)
        ->assertDontSee('Ưu đãi chung');
    $this->get('/bo-suu-tap/mua-dong')->assertOk()
        ->assertSeeInOrder(['data-collection-banner', 'Mùa đông', 'Ưu đãi chung', 'Cam kết chung'], false)
        ->assertDontSee('Banner mùa hè');
    $this->getJson('/api/storefront/v1/pages/layouts/listing?type=collection&slug=mua-he')->assertOk()
        ->assertJsonPath('data.0.key', 'vani.storefront.plp.header')->assertJsonPath('data.0.blocks.0.type', 'hero')
        ->assertJsonPath('data.1.blocks.0.data.bodyHtml', fn (string $html): bool => str_contains($html, 'Chất liệu mùa hè'));
    $this->getJson('/api/storefront/v1/pages/layouts/test-pages')->assertNotFound();

    $this->post(route('admin.storefront.builder.scope.draft', ['test-pages', "collection-{$summer->id}", 'preview']), ['blocks' => [$hero]])
        ->assertOk()->assertJsonPath('html', fn (string $html): bool => str_contains($html, 'Banner mùa hè'));

    $this->delete($editor)->assertSessionHasNoErrors();
    expect(app(BuilderEngine::class)->configuredScopes('test-pages'))->toBe([])
        ->and(DB::table('mediables')->where('media_id', $media->id)->exists())->toBeFalse();
    $this->get('/bo-suu-tap/mua-he')->assertSeeInOrder(['data-collection-banner', 'Ưu đãi chung'], false)->assertDontSee('Banner mùa hè');

    $this->actingAs(T::staff(['admin.access', 'catalog.manage']), 'staff')->put($editor, ['blocks' => []])->assertForbidden();
});
