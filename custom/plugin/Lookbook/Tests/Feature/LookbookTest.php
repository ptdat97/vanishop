<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Application\Media\ImageSizes;
use Modules\Catalog\Contracts\MediaUrls;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Testing\ImageSizeProviderContract;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Testing\StorefrontApiContract as Api;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Testing\StorefrontBlockContract;
use Plugin\Lookbook\Infrastructure\LatestLookbooksBlock;
use Plugin\Lookbook\Infrastructure\LookbookImageSizes;
use Plugin\Lookbook\Infrastructure\Lookbooks;
use Plugin\Lookbook\LookbookServiceProvider;
use Plugin\Lookbook\Persistence\Lookbook;

ImageSizeProviderContract::define('vani.lookbook', fn () => app(LookbookImageSizes::class));
StorefrontBlockContract::define('vani.lookbook latest', fn () => app(LatestLookbooksBlock::class), config: ['title' => 'Lookbook', 'limit' => 3]);

function lookbookMedia(string $name = 'anh.jpg'): Media
{
    $name = uniqid().'-'.$name;
    Storage::disk('public')->put("media/{$name}", 'x');

    return Media::query()->create(['disk' => 'public', 'path' => "media/{$name}", 'folder' => '', 'original_name' => $name, 'mime_type' => 'image/jpeg', 'size_bytes' => 1, 'width' => 1200, 'height' => 1600, 'checksum' => hash('sha256', $name)]);
}

beforeEach(function () {
    Storage::fake('public');
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(LookbookServiceProvider::ID);
        app(PluginManager::class)->enable(LookbookServiceProvider::ID);
    });
    app()->register(LookbookServiceProvider::class);
    Cache::flush();
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'lookbook.view', 'lookbook.manage', 'media.view'])->create(), 'staff');
    $this->admin = '/admin/plugins/vani-lookbook';
    $this->category = T::seed(function () {
        $category = Category::factory()->create(['slug' => 'dam']);
        $category->syncTranslations(['vi' => ['name' => 'Đầm']]);

        return $category;
    });
    $this->save = fn (array $overrides = []) => app(Lookbooks::class)->save(null, array_replace_recursive([
        'translations' => ['vi' => ['name' => 'Thu Đông 2026', 'description' => "Bộ sưu tập **mùa thu**.\n\n<script>x</script>"], 'en' => ['name' => 'Autumn Winter 2026']],
        'images' => [['media_id' => lookbookMedia('a.jpg')->id, 'alt' => ['vi' => 'Áo khoác']], ['media_id' => lookbookMedia('b.jpg')->id]],
        'category_id' => $this->category->id, 'active' => true,
    ], $overrides));
});

it('Admin: tạo lookbook (slug tự sinh từ tên, trùng thì báo lỗi), danh sách, sửa, xoá bỏ khai báo ảnh', function () {
    $media = lookbookMedia('c.jpg');
    $this->post($this->admin, ['translations' => ['vi' => ['name' => '']]])->assertSessionHasErrors('translations.vi.name');
    $this->post($this->admin, ['translations' => ['vi' => ['name' => 'Hè rực rỡ']], 'images' => [['media_id' => $media->id]], 'active' => true])->assertSessionHasNoErrors();
    $lookbook = Lookbook::query()->sole();
    expect($lookbook->slug)->toBe('he-ruc-ro')
        ->and(DB::table('mediables')->where('media_id', $media->id)->exists())->toBeTrue();

    $this->post($this->admin, ['translations' => ['vi' => ['name' => 'Hè rực rỡ']]])->assertSessionHasErrors('slug');
    $this->get("{$this->admin}?per_page=50")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Lookbook::Index')
        ->where('lookbooks.data.0.images_count', 1)->where('lookbooks.per_page', 50));
    $this->get("{$this->admin}?per_page=101")->assertSessionHasErrors('per_page');
    $this->get("{$this->admin}/{$lookbook->id}")->assertOk()->assertInertia(fn (Assert $page) => $page->component('Lookbook::Form')->where('lookbook.images.0.media_id', $media->id)->where('lookbook.lock_version', 0));

    // Sửa theo phiên bản đã mở; người thứ hai còn giữ phiên bản cũ → báo xung đột, không ghi đè.
    $edit = fn (string $name, ?int $version) => $this->put("{$this->admin}/{$lookbook->id}", ['translations' => ['vi' => ['name' => $name]], 'slug' => 'he-ruc-ro', 'images' => [['media_id' => $media->id]], 'active' => true, 'lock_version' => $version]);
    $edit('Hè 2026', 0)->assertSessionHasNoErrors();
    $edit('Ghi đè', 0)->assertSessionHasErrors('lock_version');
    $edit('Ghi đè', null)->assertSessionHasErrors('lock_version');
    expect($lookbook->fresh())->lock_version->toBe(1)
        ->and($lookbook->fresh()->text('name', 'vi'))->toBe('Hè 2026');

    $this->delete("{$this->admin}/{$lookbook->id}")->assertRedirect();
    expect(Lookbook::query()->count())->toBe(0)
        ->and(DB::table('mediables')->where('media_id', $media->id)->exists())->toBeFalse();
});

it('storefront: danh sách + chi tiết (ảnh theo thứ tự, Markdown an toàn, "Xem thêm tại"), header, sitemap; lookbook tắt → 404', function () {
    $lookbook = ($this->save)();
    $hidden = ($this->save)(['slug' => 'nhap', 'active' => false, 'translations' => ['vi' => ['name' => 'Nháp']]]);

    $this->get('/lookbook')->assertOk()->assertSee('Thu Đông 2026')->assertDontSee('Nháp');
    $html = $this->get('/lookbook/'.$lookbook->slug)->assertOk()->assertSee('<strong>mùa thu</strong>', false)->assertDontSee('<script>x', false)
        ->assertSee('data-lookbook-category', false)->assertSee('alt="Áo khoác"', false)->getContent();
    expect(strpos($html, '-a.jpg'))->toBeLessThan(strpos($html, '-b.jpg'));
    $this->get('/lookbook/'.$hidden->slug)->assertNotFound();

    $this->get('/sitemap.xml')->assertSee(url('/lookbook/'.$lookbook->slug), false)->assertDontSee('/lookbook/nhap', false);
    Api::assertRoutesDocumented(dirname(__DIR__, 2));
    Api::assertResponse($this->getJson('/api/storefront/v1/x/vani-lookbook/lookbooks'), dirname(__DIR__, 2), '/lookbooks', 'GET')->assertOk()->assertJsonCount(1, 'data');
    Api::assertResponse($this->getJson('/api/storefront/v1/x/vani-lookbook/lookbooks/'.$lookbook->slug), dirname(__DIR__, 2), '/lookbooks/{slug}', 'GET')->assertOk()->assertJsonCount(2, 'data.images');
    Api::assertResponse($this->getJson('/api/storefront/v1/x/vani-lookbook/lookbooks/'.$hidden->slug), dirname(__DIR__, 2), '/lookbooks/{slug}', 'GET')->assertNotFound();
});

it('bản tiếng Anh dùng tên đã dịch; thiếu bản dịch thì hiện tiếng Việt; danh mục bị ẩn → không có link', function () {
    $lookbook = ($this->save)();
    $this->get('/en/lookbook/'.$lookbook->slug)->assertOk()->assertSee('Autumn Winter 2026')->assertSee('mùa thu');

    T::seed(fn () => $this->category->update(['status' => 'hidden']));
    $this->get('/lookbook/'.$lookbook->slug)->assertOk()->assertDontSee('data-lookbook-category', false);
});

it('gắn sản phẩm theo từng ảnh và giữ thứ tự, chỉ hiện sản phẩm đang bán trên Blade và API theo locale', function () {
    $coat = T::product(null, [
        'style_code' => 'LOOK-COAT',
        'slug' => 'ao-khoac',
        'translations' => ['vi' => ['name' => 'Áo khoác len'], 'en' => ['name' => 'Wool coat', 'slug' => 'wool-coat']],
    ]);
    $scarf = T::product(null, ['name' => 'Khăn len', 'slug' => 'khan-len']);
    $retired = T::product(null, ['name' => 'Mẫu ngừng bán']);
    $photos = [lookbookMedia('coat.jpg'), lookbookMedia('scarf.jpg'), lookbookMedia('plain.jpg')];
    $payload = [
        'translations' => ['vi' => ['name' => 'Phối đồ mùa đông'], 'en' => ['name' => 'Winter outfits']],
        'images' => [
            ['media_id' => $photos[0]->id, 'product_ids' => [$scarf->id, $retired->id, $coat->id, $scarf->id]],
            ['media_id' => $photos[1]->id, 'product_ids' => [$scarf->id]],
            ['media_id' => $photos[2]->id],
        ],
        'active' => true,
    ];
    $this->getJson("{$this->admin}/products?q=LOOK-COAT")->assertOk()->assertJsonPath('data.0.value', (string) $coat->id);
    $this->post($this->admin, $payload)->assertSessionHasNoErrors();
    $lookbook = Lookbook::query()->sole();
    expect($lookbook->images->pluck('product_ids')->all())->toBe([[$scarf->id, $retired->id, $coat->id], [$scarf->id], null]);
    $this->get("{$this->admin}/{$lookbook->id}")->assertInertia(fn (Assert $page) => $page
        ->where('lookbook.images.0.products.0.label', 'Khăn len')->where('lookbook.images.1.products.0.value', (string) $scarf->id));

    $web = '/lookbook/'.$lookbook->slug;
    $api = '/api/storefront/v1/x/vani-lookbook/lookbooks/'.$lookbook->slug;
    $this->getJson($api)->assertJsonPath('data.images.0.products.1.name', 'Mẫu ngừng bán');
    T::seed(fn () => $retired->update(['status' => 'draft']));

    $html = $this->get('/en'.$web)->assertOk()->assertDontSee('Mẫu ngừng bán')
        ->assertSee('aria-label="View product Wool coat"', false)->getContent();
    expect(substr_count($html, 'data-lookbook-product-markers'))->toBe(2)
        ->and(substr_count($html, 'data-lookbook-products'))->toBe(2);
    $response = Api::assertResponse($this->getJson($api, ['X-Vani-Locale' => 'en']), dirname(__DIR__, 2), '/lookbooks/{slug}', 'GET')
        ->assertOk()->assertJsonCount(2, 'data.images.0.products')
        ->assertJsonPath('data.images.0.products.0.name', 'Khăn len')
        ->assertJsonPath('data.images.0.products.1.name', 'Wool coat')
        ->assertJsonPath('data.images.1.products.0.name', 'Khăn len')
        ->assertJsonPath('data.images.2.products', []);
    $coatUrl = $response->json('data.images.0.products.1.url');
    expect($coatUrl)->toBe(vani_route('storefront.product', 'wool-coat', 'en'))
        ->and(substr_count($html, 'href="'.e($coatUrl).'"'))->toBe(2);

    $this->put("{$this->admin}/{$lookbook->id}", [
        ...$payload,
        'lock_version' => 0,
        'images' => [['media_id' => $photos[0]->id], ['media_id' => $photos[1]->id, 'product_ids' => []]],
    ])->assertSessionHasNoErrors();
    expect($lookbook->fresh()->images->pluck('product_ids')->all())->toBe([[$scarf->id, $retired->id, $coat->id], null]);
});

it('từ chối danh sách sản phẩm vượt giới hạn hoặc sai kiểu và không cho người chỉ có quyền xem tìm sản phẩm', function () {
    $photo = lookbookMedia();
    $this->post($this->admin, [
        'translations' => ['vi' => ['name' => 'Phối đồ']],
        'images' => [
            ['media_id' => $photo->id, 'product_ids' => range(1, Lookbooks::MAX_PRODUCTS_PER_IMAGE + 1)],
            ['media_id' => $photo->id, 'product_ids' => [0]],
        ],
    ])->assertSessionHasErrors(['images.0.product_ids', 'images.1.product_ids.0']);
    expect(Lookbook::query()->exists())->toBeFalse();

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'lookbook.view'])->create(), 'staff')
        ->getJson("{$this->admin}/products?q=coat")->assertForbidden();
});

it('không có lookbook đang bật → sitemap không có trang lookbook', function () {
    ($this->save)(['active' => false]);

    $this->get('/sitemap.xml')->assertDontSee('/lookbook', false);
});

it('ảnh bìa ở lưới dùng kích thước riêng của plugin (lookbook.cover 600×900 cắt khung), nhân viên ghi đè được', function () {
    $cover = lookbookMedia('cover.jpg');
    ($this->save)(['cover_media_id' => $cover->id]);
    $sizes = app(ImageSizes::class);

    expect(collect($sizes->catalog())->firstWhere('name', LookbookImageSizes::COVER))->toMatchArray(['plugin' => 'vani.lookbook', 'overridden' => false])
        ->and($this->get('/lookbook')->assertOk()->getContent())->toContain($cover->checksum.'-w600h900c.jpg');

    $sizes->save(['widths' => [400, 800], 'product_ratio' => '2:3', 'quality' => 80, 'overrides' => [LookbookImageSizes::COVER => ['width' => 500, 'height' => 500, 'crop' => true]]]);
    app()->forgetInstance(ImageCache::class);
    app()->forgetInstance(MediaUrls::class);
    expect($this->get('/lookbook')->getContent())->toContain($cover->checksum.'-w500h500c.jpg')
        ->and(collect($sizes->catalog())->firstWhere('name', LookbookImageSizes::COVER)['overridden'])->toBeTrue();
});

it('chi tiết: breadcrumb dựng ở controller (Lookbook → tên, JSON-LD), ảnh qua partial ảnh của theme (ảnh đầu tải ngay)', function () {
    $lookbook = ($this->save)();

    $html = $this->get('/lookbook/'.$lookbook->slug)->assertOk()
        ->assertSee('data-vani-breadcrumb', false)->assertSee('"name":"Lookbook","item":"'.url('/lookbook').'"', false)->getContent();
    preg_match_all('#<a [^>]*rel="noopener"[^>]*>\s*<img ([^>]*)>#', $html, $images);
    expect($images[1])->toHaveCount(2)
        ->and($images[1][0])->toContain('fetchpriority="high"')->not->toContain('loading="lazy"')
        ->and($images[1][1])->toContain('loading="lazy"');
});
