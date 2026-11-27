<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Media\ImageSizes;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Media;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;
use Plugin\Cms\CmsServiceProvider;
use Plugin\Cms\Infrastructure\CmsImageSizes;
use Plugin\Cms\Infrastructure\ExtraContent;
use Plugin\Cms\Persistence\CategoryBanner;
use Plugin\Cms\Persistence\Post;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

function cmsBannerMedia(string $name): Media
{
    $name = uniqid().'-'.$name;
    Storage::disk('public')->put("media/{$name}", 'x');

    return Media::query()->create(['disk' => 'public', 'path' => "media/{$name}", 'folder' => '', 'original_name' => $name, 'mime_type' => 'image/jpeg', 'size_bytes' => 1, 'width' => 1600, 'height' => 480, 'checksum' => hash('sha256', $name)]);
}

beforeEach(function () {
    Storage::fake('public');
    ['s' => $this->s] = C::store();
    $this->product = $this->s->product;
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(CmsServiceProvider::ID);
        app(PluginManager::class)->enable(CmsServiceProvider::ID);
    });
    app()->register(CmsServiceProvider::class);
    Cache::flush();
    $this->set = fn (array $values) => T::seed(function () use ($values) {
        foreach ($values as $key => $value) {
            app(Settings::class)->set(CmsServiceProvider::ID, $key, $value);
        }
    });
    $this->pdp = fn (): string => $this->get('/san-pham/'.$this->product->slug)->assertOk()->getContent();
    $this->extension = fn () => $this->getJson('/api/storefront/v1/products/'.$this->product->slug)->assertOk()->json('data.extensions')[CmsServiceProvider::ID] ?? null;
});

it('thanh thông báo: tắt → không có HTML; nhiều dòng xoay vòng; Markdown an toàn; ngoài khung giờ → ẩn', function () {
    ($this->set)(['announcement_lines.vi' => "Miễn phí giao từ **500k**\n\n[Xem ưu đãi](/khuyen-mai) <script>alert(1)</script>\n[Bấm](javascript:alert(1))"]);
    $this->get('/')->assertOk()->assertDontSee('data-vani-cms-announcement', false);

    ($this->set)(['announcement_enabled' => true, 'announcement_bg' => '#111111', 'announcement_pause_ms' => 4000]);
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('data-vani-cms-announcement')->toContain('data-pause="4000"')->toContain('background-color:#111111;')
        ->toContain('Miễn phí giao từ <strong>500k</strong>')->toContain('<a href="/khuyen-mai">Xem ưu đãi</a>')
        ->toContain('data-next')->not->toContain('<script>alert(1)')->not->toContain('javascript:alert')
        ->and(substr_count($html, 'data-line'))->toBe(3)
        ->and($html)->toMatch('#<script src="[^"]*vani\.cms/announcement\.[^"]*" defer>#');

    // Bản tiếng Anh chưa nhập → dùng ngôn ngữ mặc định.
    $this->get('/en')->assertOk()->assertSee('data-vani-cms-announcement', false);

    ($this->set)(['announcement_from' => now(config('vanishop.locale.timezone'))->addDay()->format('Y-m-d H:i')]);
    $this->get('/')->assertOk()->assertDontSee('data-vani-cms-announcement', false);
    ($this->set)(['announcement_from' => null, 'announcement_to' => now(config('vanishop.locale.timezone'))->subMinute()->format('Y-m-d H:i')]);
    $this->get('/')->assertOk()->assertDontSee('data-vani-cms-announcement', false);
    ($this->set)(['announcement_to' => now(config('vanishop.locale.timezone'))->addHour()->format('Y-m-d H:i')]);
    $this->get('/')->assertOk()->assertSee('data-until=', false);
});

it('giới hạn bản cache CDN theo lịch thông báo', function (string $key, bool $visible, int $minutes) {
    config(['vanishop.storefront.page_cache.mode' => 'cdn', 'vanishop.storefront.page_cache.s_maxage' => 300, 'vanishop.storefront.page_cache.stale_while_revalidate' => 600]);
    $boundary = now(config('vanishop.locale.timezone'))->addMinutes($minutes)->startOfMinute();
    ($this->set)(['announcement_enabled' => true, 'announcement_lines.vi' => 'Ưu đãi', $key => $boundary->format('Y-m-d H:i')]);
    $before = time();
    $page = $this->get('/')->assertOk();
    expect(str_contains($page->getContent(), 'data-vani-cms-announcement'))->toBe($visible);
    $headers = $page->baseResponse->headers;
    $fresh = (int) $headers->getCacheControlDirective('s-maxage');
    $stale = (int) $headers->getCacheControlDirective('stale-while-revalidate');
    expect($fresh)->toBeLessThanOrEqual(300)
        ->and($fresh + $stale)->toBeGreaterThanOrEqual($boundary->timestamp - time())
        ->and($fresh + $stale)->toBeLessThanOrEqual($boundary->timestamp - $before);
})->with([['announcement_from', false, 2], ['announcement_to', true, 7]]);

it('hộp ưu đãi ở vị trí cấu hình; sản phẩm chọn ẩn thì không hiện (cả Storefront API)', function () {
    ($this->set)(['promo_box_markdown.vi' => 'Tặng **túi vải** cho đơn từ 1 triệu']);
    expect(($this->pdp)())->not->toContain('data-vani-cms-promo-box');

    ($this->set)(['promo_box_enabled' => true, 'promo_box_position' => 'after_price']);
    $html = ($this->pdp)();
    expect(substr_count($html, 'data-vani-cms-promo-box'))->toBe(1)
        ->and(strpos($html, 'data-vani-cms-promo-box'))->toBeLessThan(strpos($html, 'data-variant-form'))
        ->and(($this->extension)()['promo_box'])->toContain('<strong>túi vải</strong>');

    ($this->set)(['promo_box_position' => 'after_add_to_cart']);
    $html = ($this->pdp)();
    expect(substr_count($html, 'data-vani-cms-promo-box'))->toBe(1)
        ->and(strpos($html, 'data-vani-cms-promo-box'))->toBeGreaterThan(strpos($html, 'data-variant-form'));

    T::seed(fn () => app(ExtraContent::class)->saveProduct($this->product->id, ['hide_promo_box' => true]));
    expect(($this->pdp)())->not->toContain('data-vani-cms-promo-box')
        ->and(($this->extension)())->toBeNull();
});

it('điểm nổi bật: tối đa 8, bỏ dòng trống, hiện dưới tên và có trong Storefront API; form sản phẩm có phần "Nội dung thêm"', function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage']), 'staff');
    $lines = implode("\n", ['Lụa tơ tằm', '', '   ', ...array_map(fn (int $i): string => "Ý {$i}", range(1, 9)), '<b>đậm</b>']);
    T::seed(fn () => app(ExtraContent::class)->saveProduct($this->product->id, [ExtraContent::highlightKey('vi') => $lines]));

    expect(($this->extension)())->toBe(['highlights' => ['Lụa tơ tằm', 'Ý 1', 'Ý 2', 'Ý 3', 'Ý 4', 'Ý 5', 'Ý 6', 'Ý 7'], 'promo_box' => null]);
    $html = ($this->pdp)();
    expect($html)->toContain('data-vani-cms-highlights')->toContain('Lụa tơ tằm')->not->toContain('Ý 8')
        ->and(strpos($html, 'data-vani-cms-highlights'))->toBeGreaterThan(strpos($html, '<h1'));

    $this->get("/admin/catalog/products/{$this->product->id}/edit")->assertInertia(fn (Assert $page) => $page
        ->where('extensionSections', fn (mixed $sections) => ($section = collect($sections)->firstWhere('plugin', CmsServiceProvider::ID)) !== null
            && str_starts_with($section['values'][ExtraContent::highlightKey('vi')] ?? '', "Lụa tơ tằm\nÝ 1")));

    // Xoá hết nội dung → bỏ dòng dữ liệu.
    T::seed(fn () => app(ExtraContent::class)->saveProduct($this->product->id, [ExtraContent::highlightKey('vi') => '', 'hide_promo_box' => false]));
    expect(DB::table('plg_cms_product_extras')->count())->toBe(0)->and(($this->extension)())->toBeNull();
});

it('banner danh mục: chỉ ở danh mục của nó; thiếu ảnh mobile → ảnh desktop; link nút chỉ trong cửa hàng hoặc https; ảnh đang dùng được khai báo', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'cms.view', 'cms.manage', 'media.view'])->create(), 'staff');
    [$dresses, $shirts] = T::seed(function () {
        $dresses = Category::factory()->create(['slug' => 'dam']);
        $dresses->syncTranslations(['vi' => ['name' => 'Đầm']]);
        $shirts = Category::factory()->create(['slug' => 'ao']);
        $shirts->syncTranslations(['vi' => ['name' => 'Áo']]);

        return [$dresses, $shirts];
    });
    $this->getJson('/admin/plugins/vani-cms/banners/categories?q=dam')->assertOk()->assertExactJson(['data' => [['value' => (string) $dresses->id, 'label' => 'Đầm']]]);
    $desktop = cmsBannerMedia('desktop.jpg');
    $payload = ['category_id' => $dresses->id, 'desktop_media_id' => $desktop->id, 'button_url' => '/khuyen-mai', 'active' => true,
        'content' => ['vi' => ['title' => 'Đầm mùa thu', 'markdown' => 'Giảm **20%** <script>x</script>', 'button_text' => 'Mua ngay']]];

    foreach (['javascript:alert(1)', '//evil.test/x', 'http://shop.test', 'https://'] as $url) {
        $this->post('/admin/plugins/vani-cms/banners', [...$payload, 'button_url' => $url])->assertSessionHasErrors('button_url');
    }
    $this->post('/admin/plugins/vani-cms/banners', [...$payload, 'category_id' => 999999])->assertSessionHasErrors('category_id');
    $this->post('/admin/plugins/vani-cms/banners', $payload)->assertSessionHasNoErrors()->assertRedirect();
    $this->post('/admin/plugins/vani-cms/banners', $payload)->assertSessionHasErrors('category_id'); // mỗi danh mục một banner
    $banner = CategoryBanner::query()->sole();
    expect(DB::table('mediables')->where('media_id', $desktop->id)->exists())->toBeTrue();

    $html = $this->get('/danh-muc/dam')->assertOk()->getContent();
    expect($html)->toContain('data-vani-cms-category-banner')->toContain('Đầm mùa thu')->toContain('<strong>20%</strong>')
        ->toContain('href="/khuyen-mai"')->not->toContain('<script>x');
    preg_match('/<source media="\(max-width: 767px\)" srcset="([^"]+)"/', $html, $source);
    expect($source[1] ?? null)->toBe(vani_image($desktop->id, 800));
    $this->get('/danh-muc/ao')->assertOk()->assertDontSee('data-vani-cms-category-banner', false);

    $this->get('/admin/plugins/vani-cms/banners')->assertInertia(fn (Assert $page) => $page->component('Cms::Banners/Index')->where('banners.0.category', 'Đầm'));
    $this->get("/admin/plugins/vani-cms/banners/{$banner->id}")->assertInertia(fn (Assert $page) => $page->component('Cms::Banners/Form')->where('banner.desktop.id', $desktop->id));

    // Đổi ảnh mobile riêng; tắt banner → không hiện.
    $mobile = cmsBannerMedia('mobile.jpg');
    $this->put("/admin/plugins/vani-cms/banners/{$banner->id}", [...$payload, 'mobile_media_id' => $mobile->id])->assertSessionHasNoErrors();
    expect($this->get('/danh-muc/dam')->getContent())->toContain('srcset="'.vani_image($mobile->id, 800).'"');
    $this->put("/admin/plugins/vani-cms/banners/{$banner->id}", [...$payload, 'active' => false])->assertSessionHasNoErrors();
    $this->get('/danh-muc/dam')->assertOk()->assertDontSee('data-vani-cms-category-banner', false);
    expect(DB::table('mediables')->where('media_id', $mobile->id)->exists())->toBeFalse();

    $this->delete("/admin/plugins/vani-cms/banners/{$banner->id}")->assertRedirect();
    expect(CategoryBanner::query()->count())->toBe(0)->and(DB::table('mediables')->where('media_id', $desktop->id)->exists())->toBeFalse();
});

it('tạo và sửa banner cùng danh mục, hiện đúng ngôn ngữ và dùng chung bản ghi với trang CMS', function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.manage', 'cms.manage', 'cms.view', 'media.view']), 'staff');
    $desktop = cmsBannerMedia('category.jpg');
    $input = [
        'slug' => 'mua-he', 'status' => 'active', 'position' => 0,
        'translations' => ['vi' => ['name' => 'Mùa hè'], 'en' => ['name' => 'Summer', 'slug' => 'summer']],
    ];
    $bannerFields = ['active' => true, 'desktop_media_id' => $desktop->id, 'title_vi' => 'Ưu đãi mùa hè', 'title_en' => 'Summer offer'];

    $this->post('/admin/catalog/categories', [...$input, 'extensions' => ['vani-cms' => ['banner' => $bannerFields]]])
        ->assertSessionHasNoErrors()->assertRedirect();
    $category = Category::query()->where('slug', 'mua-he')->sole();
    $banner = CategoryBanner::query()->sole();
    expect($banner->category_id)->toBe($category->id);

    $this->get("/admin/catalog/categories/{$category->id}/edit")->assertInertia(fn (Assert $page) => $page
        ->where('extensionSections', fn (mixed $sections) => collect($sections)->firstWhere('key', 'banner')['values']['title_en'] === 'Summer offer'));
    $this->get("/admin/plugins/vani-cms/banners/{$banner->id}")->assertInertia(fn (Assert $page) => $page
        ->where('banner.content.vi.title', 'Ưu đãi mùa hè'));
    $this->get('/danh-muc/mua-he')->assertSee('Ưu đãi mùa hè');
    $this->get('/en/category/summer')->assertSee('Summer offer');
    expect(DB::table('mediables')->where('media_id', $desktop->id)->exists())->toBeTrue();

    $this->put("/admin/catalog/categories/{$category->id}", [...$input, 'lock_version' => $category->lock_version,
        'extensions' => ['vani-cms' => ['banner' => ['active' => false]]],
    ])->assertSessionHasNoErrors();
    $this->get('/danh-muc/mua-he')->assertDontSee('data-vani-cms-category-banner', false);
    expect($banner->fresh()->content['en']['title'])->toBe('Summer offer');
});

it('kiểm tra quyền CMS và rollback thay đổi danh mục nếu dữ liệu banner không hợp lệ', function () {
    $category = Category::factory()->create(['slug' => 'ao-mua-he'])->refresh();
    $input = [
        'slug' => $category->slug, 'status' => 'active', 'position' => 1, 'lock_version' => $category->lock_version,
        'translations' => ['vi' => ['name' => 'Áo mùa hè']],
    ];
    $edit = "/admin/catalog/categories/{$category->id}";
    $video = cmsBannerMedia('other-media.mp4');
    $video->update(['mime_type' => 'video/mp4']);

    $this->actingAs(T::staff(['admin.access', 'catalog.manage', 'cms.manage']), 'staff');
    $this->put($edit, [...$input, 'extensions' => ['vani-cms' => ['banner' => ['desktop_media_id' => $video->id]]]])
        ->assertSessionHasErrors('extensions.vani-cms.banner.desktop_media_id');
    $this->put($edit, [...$input, 'extensions' => ['vani-cms' => ['banner' => ['button_url' => '//other.test']]]])
        ->assertSessionHasErrors('extensions.vani-cms.banner.button_url');
    expect($category->fresh()->lock_version)->toBe($category->lock_version);
    expect(CategoryBanner::query()->exists())->toBeFalse();

    $this->actingAs(T::staff(['admin.access', 'catalog.manage', 'cms.view']), 'staff');
    $this->get($edit.'/edit')->assertInertia(fn (Assert $page) => $page->where('extensionSections', []));
    $this->put($edit, [...$input, 'extensions' => ['vani-cms' => ['banner' => ['active' => true, 'title_vi' => 'Tiêu đề trái quyền']]]])
        ->assertSessionHasNoErrors();
    expect(CategoryBanner::query()->exists())->toBeFalse();
});

it('ảnh CMS dùng kích thước theo tên và banner trên tablet vẫn có srcset', function () {
    $category = Category::factory()->create(['slug' => 'banner-kich-thuoc']);
    $category->syncTranslations(['vi' => ['name' => 'Banner kích thước']]);
    $image = cmsBannerMedia('responsive.jpg');
    T::seed(function () use ($category, $image): void {
        $sizes = app(ImageSizes::class);
        $sizes->save([
            'widths' => [400, 800, 1200], 'product_ratio' => $sizes->ratioCode(), 'quality' => $sizes->quality(),
            'overrides' => [
                CmsImageSizes::BANNER_DESKTOP => ['width' => 1300, 'height' => null, 'crop' => false],
                CmsImageSizes::BANNER_MOBILE => ['width' => 600, 'height' => null, 'crop' => false],
                CmsImageSizes::POST_COVER => ['width' => 1000, 'height' => null, 'crop' => false],
            ],
        ]);
        app(ExtraContent::class)->saveBanner(null, $category->id, ['desktop_media_id' => $image->id, 'active' => true]);
        Post::query()->create([
            'slug' => 'bia-theo-ten', 'title' => 'Bìa theo tên', 'body' => 'Bài viết mẫu',
            'status' => Post::PUBLISHED, 'published_at' => now(), 'cover_media_id' => $image->id,
        ]);
    });

    $html = $this->get('/danh-muc/banner-kich-thuoc')->assertOk()->getContent();
    expect($html)->toMatch('/<source media="\(max-width: 767px\)" srcset="[^"]+-w600\.[^"]+"/')
        ->toMatch('/<source media="\(min-width: 1280px\)" srcset="[^"]+-w1300\.[^"]+"/');
    preg_match('/<img src="[^"]+-w1300\.[^"]+" alt=""\s+srcset="([^"]+)"/', $html, $img);
    expect($img[1] ?? '')->toContain(' 400w', ' 800w', ' 1200w');

    $article = $this->get('/tin-tuc/bia-theo-ten')->assertOk()->getContent();
    expect($article)->toMatch('/<meta property="og:image" content="[^"]+-w1000\.[^"]+"/');
});
