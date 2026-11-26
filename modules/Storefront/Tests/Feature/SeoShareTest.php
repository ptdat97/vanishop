<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Application\Products\VariantImages;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Storefront\Application\Seo\StoreShare;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| SEO storefront (stabilization S3): mô tả + thẻ chia sẻ có dự phòng, JSON-LD cửa hàng, canonical không kèm bộ lọc,
| trang tìm kiếm noindex, đổi slug → 301.
*/

beforeEach(function () {
    ['brand' => $this->brand, 's' => $this->s] = C::store();
    $this->product = $this->s->product;
    $this->category = T::seed(fn () => Category::factory()->create(['slug' => 'dam']));
    $this->category->syncTranslations(['vi' => ['name' => 'Đầm', 'description' => '<p>Đầm lụa   mềm, <b>mát</b> cho mùa hè.</p>']]);
    $this->product->categories()->sync([$this->category->id]);
    $this->setting = fn (string $key, mixed $value) => T::seed(fn () => app(Settings::class)->set('core', $key, $value));
});

it('danh mục: tiêu đề + mô tả SEO nhập ở Admin; chưa nhập → tên + đoạn đầu mô tả (bỏ HTML)', function () {
    $this->get('/danh-muc/dam')->assertOk()
        ->assertSee('<title>Đầm — ', false)
        ->assertSee('<meta name="description" content="Đầm lụa mềm, mát cho mùa hè.">', false)
        ->assertSee('<meta property="og:title" content="Đầm">', false)
        ->assertSee('<meta property="og:url" content="'.url('/danh-muc/dam').'">', false);

    $this->category->syncTranslations(['vi' => ['name' => 'Đầm', 'meta_title' => 'Đầm nữ đẹp 2026', 'meta_description' => 'Mua đầm nữ giao nhanh.']]);
    $this->get('/danh-muc/dam')->assertOk()
        ->assertSee('<title>Đầm nữ đẹp 2026 — ', false)
        ->assertSee('<meta name="description" content="Mua đầm nữ giao nhanh.">', false)
        ->assertSee('<meta property="og:description" content="Mua đầm nữ giao nhanh.">', false);
});

it('trang chủ: mô tả + ảnh chia sẻ của cửa hàng; JSON-LD Organization + WebSite có ô tìm kiếm', function () {
    Storage::fake('public');
    $image = T::seed(fn () => app(MediaLibrary::class)->store(UploadedFile::fake()->image('share.jpg', 1600, 840)));
    ($this->setting)(StoreShare::DESCRIPTION_SETTING, 'Thời trang nữ thiết kế, giao nhanh toàn quốc.');
    ($this->setting)(StoreShare::IMAGE_SETTING, $image->id);

    $html = $this->get('/')->assertOk()
        ->assertSee('<meta name="description" content="Thời trang nữ thiết kế, giao nhanh toàn quốc.">', false)
        ->assertSee('<meta property="og:type" content="website">', false)
        ->assertSee('<meta property="og:image" content="'.url($image->url(1600)).'">', false)
        ->assertSee('<meta name="twitter:card" content="summary_large_image">', false)
        ->getContent();

    preg_match('#<script type="application/ld\+json">(\{"@context":"https://schema.org","@graph".*?)</script>#', $html, $match);
    $graph = json_decode($match[1] ?? '{}', true)['@graph'] ?? [];
    expect(array_column($graph, '@type'))->toBe(['Organization', 'WebSite'])
        ->and($graph[0]['logo'])->toBe(url($image->url(1600)))
        ->and($graph[1]['potentialAction']['target']['urlTemplate'])->toBe(url('/tim-kiem').'?q={search_term_string}');

    // Trang khác không có JSON-LD cửa hàng; mô tả cửa hàng là mô tả dự phòng.
    $this->get('/thuong-hieu')->assertOk()->assertDontSee('"@graph"', false)
        ->assertSee('<meta name="description" content="Thời trang nữ thiết kế, giao nhanh toàn quốc.">', false);
});

it('sản phẩm: og:type product, ảnh chia sẻ là ảnh đầu gallery', function () {
    Storage::fake('public');
    T::seed(fn () => app(VariantImages::class)->addFiles($this->product, $this->s, [UploadedFile::fake()->image('a.jpg', 600, 900)]));

    $this->get("/san-pham/{$this->product->slug}")->assertOk()
        ->assertSee('<meta property="og:type" content="product">', false)
        ->assertSee('<meta property="og:image" content="http', false);
});

it('canonical không kèm bộ lọc, sắp xếp, utm; trang 2 tự canonical; tìm kiếm noindex, không thẻ chia sẻ', function () {
    $this->get("/thuong-hieu/{$this->brand->slug}?sort=price_asc&size=S")->assertOk()
        ->assertSee('<link rel="canonical" href="'.url("/thuong-hieu/{$this->brand->slug}").'">', false);
    $this->get('/danh-muc/dam?page=2&sort=newest')->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/danh-muc/dam').'?page=2">', false);
    $this->get('/?utm_source=facebook')->assertOk()
        ->assertSee('<link rel="canonical" href="'.url('/').'">', false);

    $this->get('/tim-kiem?q=dam')->assertOk()
        ->assertSee('<meta name="robots" content="noindex">', false)
        ->assertDontSee('og:title', false);
});

it('đổi slug: link cũ chuyển 301 sang slug mới (giữ query); slug cũ được dùng lại thì thuộc bản ghi mới', function () {
    $old = $this->product->slug;
    T::seed(fn () => $this->product->update(['slug' => 'dam-lua-moi']));
    $this->get("/san-pham/{$old}?sku={$this->s->sku}")->assertStatus(301)->assertRedirect("/san-pham/dam-lua-moi?sku={$this->s->sku}");

    T::seed(fn () => $this->category->update(['slug' => 'dam-nu']));
    $this->get('/danh-muc/dam?page=2')->assertStatus(301)->assertRedirect('/danh-muc/dam-nu?page=2');

    $brandSlug = $this->brand->slug;
    T::seed(fn () => $this->brand->update(['slug' => 'lumiere-paris']));
    $this->get("/thuong-hieu/{$brandSlug}")->assertStatus(301)->assertRedirect('/thuong-hieu/lumiere-paris');

    // Sản phẩm khác lấy lại slug cũ → slug cũ là trang của sản phẩm đó.
    T::product($this->brand->id, ['name' => 'Áo mới', 'slug' => $old]);
    $this->get("/san-pham/{$old}")->assertOk()->assertSee('Áo mới');
});

it('L44: sản phẩm ngừng bán (kể cả link slug cũ) → 410, noindex, gợi ý cùng danh mục; slug chưa từng có → 404', function () {
    $old = $this->product->slug;
    T::product($this->brand->id, ['name' => 'Đầm cùng danh mục', 'category_ids' => [$this->category->id]]);
    T::seed(fn () => $this->product->update(['slug' => 'dam-cu', 'status' => 'archived']));

    foreach ([$old, 'dam-cu'] as $slug) {
        $this->get("/san-pham/{$slug}")->assertStatus(410)
            ->assertSee('Sản phẩm đã ngừng bán')
            ->assertSee('<meta name="robots" content="noindex">', false)
            ->assertSee('href="'.url('/danh-muc/dam').'"', false);
    }
    $this->get('/san-pham/chua-tung-co')->assertNotFound();
    expect(Product::query()->count())->toBe(2)->and(Brand::query()->count())->toBe(1);
});

it('L44: slug riêng của bản dịch đổi → link cũ của ngôn ngữ đó 301 sang slug mới', function () {
    config(['vanishop.locale.supported' => ['vi', 'en']]);
    $this->product->syncTranslations(['vi' => ['name' => 'Đầm lụa'], 'en' => ['name' => 'Silk dress', 'slug' => 'silk-dress']]);
    $this->product->syncTranslations(['vi' => ['name' => 'Đầm lụa'], 'en' => ['name' => 'Silk dress', 'slug' => 'silk-midi-dress']]);

    $this->get('/en/products/silk-dress')->assertStatus(301)->assertRedirect('/en/products/silk-midi-dress');
});
