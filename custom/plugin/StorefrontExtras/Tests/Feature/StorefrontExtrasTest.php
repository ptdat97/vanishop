<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginAssets;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Testing\StorefrontBlockContract;
use Modules\Tenancy\Contracts\Settings;
use Plugin\StorefrontExtras\Infrastructure\Blocks\BannerGridBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\BestsellersBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\FeaturesBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\MarkdownBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\ProductTabsBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\RecentlyViewedBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\SlideshowBlock;
use Plugin\StorefrontExtras\Infrastructure\Blocks\VideoHeroBlock;
use Plugin\StorefrontExtras\StorefrontExtrasServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

StorefrontBlockContract::define('vani.storefront-extras bestsellers', fn () => app(BestsellersBlock::class), config: ['title' => 'Bán chạy', 'limit' => 4, 'days' => '30']);
StorefrontBlockContract::define('vani.storefront-extras recently viewed', fn () => app(RecentlyViewedBlock::class), config: ['title' => 'Đã xem', 'layout' => 'scroll']);
StorefrontBlockContract::define('vani.storefront-extras video hero', fn () => app(VideoHeroBlock::class), config: ['video_url' => 'https://cdn.shop.test/hero.mp4', 'title' => 'Hè', 'autoplay' => true]);
StorefrontBlockContract::define('vani.storefront-extras slideshow', fn () => app(SlideshowBlock::class), config: ['slide_1_image' => 'https://cdn.shop.test/1.jpg', 'slide_1_title' => 'Mới']);
StorefrontBlockContract::define('vani.storefront-extras banner grid', fn () => app(BannerGridBlock::class), config: ['layout' => '3', 'cell_1_image' => '/img/a.jpg', 'cell_2_image' => '/img/b.jpg']);
StorefrontBlockContract::define('vani.storefront-extras product tabs', fn () => app(ProductTabsBlock::class), config: ['tab_1_label' => 'Mới', 'tab_1_source' => 'newest']);
StorefrontBlockContract::define('vani.storefront-extras features', fn () => app(FeaturesBlock::class), config: ['item_1_icon' => 'truck', 'item_1_title' => 'Giao nhanh']);
StorefrontBlockContract::define('vani.storefront-extras markdown', fn () => app(MarkdownBlock::class), config: ['body' => '**Chào**']);

beforeEach(function () {
    ['s' => $this->s, 'm' => $this->m, 'brand' => $this->brand] = C::store();
    $this->assets = storage_path('framework/testing/extras-assets-'.uniqid());
    app()->instance(PluginAssets::class, new PluginAssets($this->assets, 'https://shop.test/plugins'));
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(StorefrontExtrasServiceProvider::ID);
        app(PluginManager::class)->enable(StorefrontExtrasServiceProvider::ID);
    });
    app()->register(StorefrontExtrasServiceProvider::class);
    Cache::flush();
});

afterEach(fn () => File::deleteDirectory($this->assets));

it('Bán chạy: theo số lượng đã bán, bỏ sản phẩm không còn bán, thiếu thì bù sản phẩm mới nhất', function () {
    $hidden = T::product($this->brand->id, ['status' => 'draft']);
    $other = T::product($this->brand->id, ['name' => 'Áo mới']);
    app()->instance(OrderStatistics::class, Mockery::mock(OrderStatistics::class, fn ($mock) => $mock->shouldReceive('topStyles')->andReturn([
        ['style_id' => $hidden->id, 'quantity' => 9], ['style_id' => $this->s->style_id, 'quantity' => 5],
    ])));

    $products = app(BestsellersBlock::class)->resolve(['limit' => 2, 'days' => '30'], 'vi')['products'];
    expect(array_column($products, 'id'))->toBe([$this->s->style_id, $other->id]);
});

it('Video hero chỉ nhận https mp4/webm; Markdown bỏ HTML thô và link javascript:', function () {
    expect(fn () => app(VideoHeroBlock::class)->resolve(['video_url' => 'http://x.test/a.mp4'], 'vi'))->toThrow(InvalidArgumentException::class)
        ->and(fn () => app(VideoHeroBlock::class)->resolve(['video_url' => 'https://x.test/a.mov'], 'vi'))->toThrow(InvalidArgumentException::class);

    $html = app(MarkdownBlock::class)->resolve(['body' => "<script>alert(1)</script>\n\n[bấm](javascript:alert(1)) **đậm**"], 'vi')['html'];
    expect($html)->not->toContain('<script')->not->toContain('javascript:')->toContain('<strong>đậm</strong>');
});

it('Sản phẩm theo tab: tab thiếu tên/nguồn hoặc không có sản phẩm bị bỏ; chọn tay giữ thứ tự id', function () {
    $tabs = app(ProductTabsBlock::class)->resolve([
        'tab_1_label' => 'Chọn', 'tab_1_source' => 'ids', 'tab_1_value' => "{$this->m->style_id}, {$this->s->style_id}",
        'tab_2_label' => 'Trống', 'tab_2_source' => 'category', 'tab_2_value' => 'khong-co',
        'tab_3_label' => '', 'tab_3_source' => 'newest',
    ], 'vi')['tabs'];

    expect(array_column($tabs, 'label'))->toBe(['Chọn']);
});

it('trang chủ render khối của plugin + script; giỏ có thanh "Đã xem" (khung rỗng, bỏ sản phẩm trong giỏ), tắt được', function () {
    T::seed(fn () => app(PageBlocks::class)->saveHome([
        ['type' => 'extras_features', 'config' => ['item_1_icon' => 'truck', 'item_1_title' => 'Giao nhanh 2h']],
        ['type' => 'extras_recently_viewed', 'config' => ['title' => 'Bạn đã xem gần đây']],
    ]));
    $this->get('/')->assertOk()->assertSee('Giao nhanh 2h')->assertSee('data-extras-recent', false)
        ->assertSee('https://shop.test/plugins/vani.storefront-extras/extras.', false);

    $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 1]);
    $this->get('/gio-hang')->assertOk()->assertSee('data-exclude-slugs="'.$this->s->style->slug.'"', false);

    T::seed(fn () => app(Settings::class)->set(StorefrontExtrasServiceProvider::ID, 'recent_on_cart', false));
    $this->get('/gio-hang')->assertOk()->assertDontSee('data-extras-recent', false);
});
