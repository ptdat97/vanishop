<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
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
use Plugin\Cms\Infrastructure\ExtraContent;
use Plugin\Cms\Persistence\CategoryBanner;

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
    $this->style = $this->s->style;
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
    $this->pdp = fn (): string => $this->get('/san-pham/'.$this->style->slug)->assertOk()->getContent();
    $this->extension = fn () => $this->getJson('/api/storefront/v1/products/'.$this->style->slug)->assertOk()->json('data.extensions')[CmsServiceProvider::ID] ?? null;
});

it('thanh thông báo: tắt → không có HTML; nhiều dòng xoay vòng; Markdown an toàn; ngoài khung giờ → ẩn', function () {
    ($this->set)(['announcement_lines.vi' => "Miễn phí giao từ **500k**\n\n[Xem ưu đãi](/khuyen-mai) <script>alert(1)</script>\n[Bấm](javascript:alert(1))"]);
    $this->get('/')->assertOk()->assertDontSee('data-vani-cms-announcement', false);

    ($this->set)(['announcement_enabled' => true, 'announcement_bg' => '#111111', 'announcement_pause_ms' => 4000]);
    $html = $this->get('/')->assertOk()->getContent();
    expect($html)->toContain('data-vani-cms-announcement')->toContain('data-pause="4000"')->toContain('background-color:#111111;')
        ->toContain('Miễn phí giao từ <strong>500k</strong>')->toContain('<a href="/khuyen-mai">Xem ưu đãi</a>')
        ->toContain('data-next')->not->toContain('<script>alert(1)')->not->toContain('javascript:alert')
        ->and(substr_count($html, 'data-line'))->toBe(3);

    // Bản tiếng Anh chưa nhập → dùng ngôn ngữ mặc định.
    $this->get('/en')->assertOk()->assertSee('data-vani-cms-announcement', false);

    ($this->set)(['announcement_from' => now(config('vanishop.locale.timezone'))->addDay()->format('Y-m-d H:i')]);
    $this->get('/')->assertOk()->assertDontSee('data-vani-cms-announcement', false);
    ($this->set)(['announcement_from' => null, 'announcement_to' => now(config('vanishop.locale.timezone'))->subMinute()->format('Y-m-d H:i')]);
    $this->get('/')->assertOk()->assertDontSee('data-vani-cms-announcement', false);
    ($this->set)(['announcement_to' => now(config('vanishop.locale.timezone'))->addHour()->format('Y-m-d H:i')]);
    $this->get('/')->assertOk()->assertSee('data-until=', false);
});

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

    T::seed(fn () => app(ExtraContent::class)->saveProduct($this->style->id, ['hide_promo_box' => true]));
    expect(($this->pdp)())->not->toContain('data-vani-cms-promo-box')
        ->and(($this->extension)())->toBeNull();
});

it('điểm nổi bật: tối đa 8, bỏ dòng trống, hiện dưới tên và có trong Storefront API; form sản phẩm có phần "Nội dung thêm"', function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage']), 'staff');
    $lines = implode("\n", ['Lụa tơ tằm', '', '   ', ...array_map(fn (int $i): string => "Ý {$i}", range(1, 9)), '<b>đậm</b>']);
    T::seed(fn () => app(ExtraContent::class)->saveProduct($this->style->id, [ExtraContent::highlightKey('vi') => $lines]));

    expect(($this->extension)())->toBe(['highlights' => ['Lụa tơ tằm', 'Ý 1', 'Ý 2', 'Ý 3', 'Ý 4', 'Ý 5', 'Ý 6', 'Ý 7'], 'promo_box' => null]);
    $html = ($this->pdp)();
    expect($html)->toContain('data-vani-cms-highlights')->toContain('Lụa tơ tằm')->not->toContain('Ý 8')
        ->and(strpos($html, 'data-vani-cms-highlights'))->toBeGreaterThan(strpos($html, '<h1'));

    $this->get("/admin/catalog/products/{$this->style->id}/edit")->assertInertia(fn (Assert $page) => $page
        ->where('extensionSections', fn ($sections) => ($section = collect($sections)->firstWhere('plugin', CmsServiceProvider::ID)) !== null
            && str_starts_with($section['values'][ExtraContent::highlightKey('vi')] ?? '', "Lụa tơ tằm\nÝ 1")));

    // Xoá hết nội dung → bỏ dòng dữ liệu.
    T::seed(fn () => app(ExtraContent::class)->saveProduct($this->style->id, [ExtraContent::highlightKey('vi') => '', 'hide_promo_box' => false]));
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
