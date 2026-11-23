<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Application\Products\VariantImages;
use Modules\Catalog\Contracts\CollectionPages;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Storefront\Application\Builder\BuilderEngine;
use Modules\Tenancy\Contracts\Settings;
use Plugin\StorefrontExtras\StorefrontExtrasServiceProvider;
use Plugin\Wishlist\WishlistServiceProvider;

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Rào chắn mobile & tốc độ của storefront (stabilization S1): ở màn điện thoại, mọi trang chính không cuộn ngang, ảnh có
| kích thước (không nhảy trang), JS/CSS tải lúc mở trang trong ngân sách, ảnh trong màn hình đầu của trang danh sách và
| trang sản phẩm không lazy, hiệu ứng hiện dần không ẩn nội dung màn hình đầu. Số đo 2026-11-18: JS 34 KB (trang sản
| phẩm 48 KB, thêm PhotoSwipe), CSS 74–79 KB — chưa nén.
*/

const MOBILE_JS_BUDGET_KB = 64;
const MOBILE_CSS_BUDGET_KB = 96;

beforeEach(function () {
    Storage::fake('public');
    ['s' => $this->s, 'brand' => $this->brand] = C::store();
    $this->product = $this->s->style;
    $this->category = T::seed(fn () => Category::factory()->create(['slug' => 'dam']));
    $this->category->syncTranslations(['vi' => ['name' => 'Đầm']]);
    $this->product->categories()->sync([$this->category->id]);
    T::seed(fn () => app(VariantImages::class)->addFiles($this->product, $this->s, [
        UploadedFile::fake()->image('truoc.jpg', 600, 900),
        UploadedFile::fake()->image('sau.jpg', 610, 900),
    ]));
});

/**
 * Số đo của trang đang mở: cuộn ngang (px), JS/CSS đã tải (KB, chưa nén), ảnh thiếu width/height, ảnh lazy và phần tử
 * đang chờ hiệu ứng nằm trong màn hình đầu.
 *
 * @return array{overflow: int, js_kb: int, css_kb: int, unsized: list<string>, lazy_first_screen: list<string>, hidden_first_screen: int}
 */
function mobilePageMetrics(mixed $page): array
{
    return $page->script(<<<'JS'
        () => {
            const resources = performance.getEntriesByType('resource');
            const kb = (suffix) => Math.round(resources.filter((r) => new URL(r.name).pathname.endsWith(suffix)).reduce((sum, r) => sum + r.decodedBodySize, 0) / 1024);
            const inFirstScreen = (el) => {
                const rect = el.getBoundingClientRect();
                const clip = el.closest('[data-gallery-track]')?.getBoundingClientRect();
                const left = Math.max(0, rect.left, clip?.left ?? 0);
                const right = Math.min(window.innerWidth, rect.right, clip?.right ?? window.innerWidth);
                return right > left && rect.top < window.innerHeight && rect.bottom > 0;
            };
            const images = [...document.images];
            return {
                overflow: document.documentElement.scrollWidth - window.innerWidth,
                js_kb: kb('.js'),
                css_kb: kb('.css'),
                unsized: images.filter((img) => !(img.getAttribute('width') && img.getAttribute('height'))).map((img) => img.src),
                lazy_first_screen: images.filter((img) => img.loading === 'lazy' && !img.closest('[data-gallery-thumbs]') && inFirstScreen(img)).map((img) => img.src),
                hidden_first_screen: [...document.querySelectorAll('[data-vani-motion-pending]')].filter(inFirstScreen).length,
            };
        }
    JS);
}

it('trang chính ở màn 375px: không cuộn ngang, ảnh có kích thước, JS/CSS trong ngân sách, màn hình đầu không bị ẩn', function (string $path, bool $mainImagesFirst) {
    $path = str_replace('{product}', $this->product->slug, $path);
    $page = visit($path)->on()->mobile();
    $metrics = mobilePageMetrics($page);

    expect($metrics['overflow'])->toBeLessThanOrEqual(0, "{$path}: cuộn ngang")
        ->and($metrics['unsized'])->toBe([], "{$path}: ảnh thiếu width/height")
        ->and($metrics['js_kb'])->toBeLessThanOrEqual(MOBILE_JS_BUDGET_KB, "{$path}: JS {$metrics['js_kb']} KB")
        ->and($metrics['css_kb'])->toBeLessThanOrEqual(MOBILE_CSS_BUDGET_KB, "{$path}: CSS {$metrics['css_kb']} KB")
        ->and($metrics['hidden_first_screen'])->toBe(0, "{$path}: nội dung màn hình đầu bị ẩn chờ hiệu ứng");
    if ($mainImagesFirst) {
        expect($metrics['lazy_first_screen'])->toBe([], "{$path}: ảnh màn hình đầu bị lazy");
    }
    $page->assertNoJavaScriptErrors();
})->with([
    'trang chủ' => ['/', false],
    'danh mục' => ['/danh-muc/dam', true],
    'sản phẩm' => ['/san-pham/{product}', true],
    'tìm kiếm' => ['/tim-kiem?q=dam', true],
    'thương hiệu' => ['/thuong-hieu', false],
    'mọi danh mục' => ['/danh-muc', false],
    'giỏ hàng' => ['/gio-hang', false],
    'tra cứu đơn' => ['/tra-cuu-don', false],
    'đăng nhập' => ['/tai-khoan/dang-nhap', false],
]);

it('bố cục theo chiều rộng giữ trang vừa màn hình và bộ lọc đúng trạng thái', function () {
    foreach (['/', '/danh-muc/dam', "/san-pham/{$this->product->slug}"] as $path) {
        $page = visit($path);
        foreach ([320, 375, 390, 768, 1024, 1280, 1440] as $width) {
            $page->resize($width, 844)->assertNoJavaScriptErrors();
            expect(mobilePageMetrics($page)['overflow'])->toBeLessThanOrEqual(0, "{$path} ({$width}px): cuộn ngang");
            if ($path === '/danh-muc/dam') {
                $page->assertVisible('[data-vani-drawer-open="vani-listing-filters"]')
                    ->assertScript("document.querySelector('#vani-listing-filters').open", false);
            }
        }
    }
});

it('sắp xếp mobile giữ bộ lọc và focus khi cập nhật kết quả tại chỗ', function () {
    $page = visit('/tim-kiem?q=lua&brand=lumiere')->on()->mobile();
    $page->script('() => { window.originalListing = true; }');

    $page->select('#vani-listing-sort', 'price_desc')
        ->assertQueryStringHas('sort', 'price_desc')
        ->assertQueryStringHas('q', 'lua')
        ->assertQueryStringHas('brand', 'lumiere')
        ->assertScript('window.originalListing', true)
        ->assertScript("document.activeElement?.id === 'vani-listing-sort'", true)
        ->assertSeeIn('[data-vani-listing-status]', '1 sản phẩm')
        ->assertNoJavaScriptErrors();
});

it('gallery mobile đổi ảnh ngay và chỉ tải PhotoSwipe khi khách phóng to', function () {
    $page = visit("/san-pham/{$this->product->slug}?sku={$this->s->sku}")->on()->mobile();
    $page->assertScript("performance.getEntriesByType('resource').some(({ name }) => /photoswipe|swiper-bundle/.test(name))", false)
        ->click('[data-gallery-next]')
        ->assertSeeIn('[data-gallery-position]', '2')
        ->assertVisible('[data-gallery-thumb][data-index="1"][aria-current="true"]')
        ->click('[data-gallery-slide]:nth-child(2) a')
        ->assertVisible('.pswp')
        ->assertNoJavaScriptErrors();
});

it('mở bộ lọc bằng bàn phím và trả focus về nút lọc sau khi chọn thương hiệu', function (int $width) {
    $trigger = '[data-vani-drawer-open="vani-listing-filters"]';
    $page = visit('/tim-kiem')->resize($width, 844);
    $page->keys($trigger, 'Enter')
        ->assertVisible('#vani-listing-filters[open]')
        ->keys('#vani-listing-filters [data-vani-drawer-close]', 'Escape')
        ->assertScript("document.activeElement?.dataset.vaniDrawerOpen === 'vani-listing-filters'", true)
        ->keys($trigger, 'Enter')
        ->keys('#vani-listing-filters [data-filter="brand"] a', 'Enter')
        ->assertSee('Bỏ tất cả bộ lọc')
        ->assertScript("document.activeElement?.dataset.vaniDrawerOpen === 'vani-listing-filters'", true)
        ->click($trigger)
        ->assertVisible('#vani-listing-filters [data-filter="brand"] a[aria-current="true"]')
        ->assertNoJavaScriptErrors();
})->with([375, 1280]);

it('thẻ sản phẩm mở ảnh, lưu yêu thích và mở đăng nhập xem giá bằng các điểm bấm riêng', function () {
    T::seed(function (): void {
        app(Settings::class)->set('core', 'storefront.price_visibility', 'members');
        app(PluginManager::class)->install(WishlistServiceProvider::ID);
        app(PluginManager::class)->enable(WishlistServiceProvider::ID);
    });
    app()->register(WishlistServiceProvider::class);
    $card = '[data-vani-product="'.$this->product->slug.'"]';
    $wishlist = $card.' [data-vani-wishlist-button]';
    $page = visit('/danh-muc/dam')->resize(390, 844);

    $page->assertAttribute($wishlist, 'aria-pressed', 'false')
        ->click($wishlist)
        ->assertAttribute($wishlist, 'aria-pressed', 'true')
        ->assertPathIs('/danh-muc/dam');
    $page->script(<<<'JS'
        () => {
            const image = document.querySelector('[data-vani-product] img');
            const { left, top, width, height } = image.getBoundingClientRect();
            document.elementFromPoint(left + width / 2, top + height / 2).click();
        }
    JS);
    $page->assertPathIs('/san-pham/'.$this->product->slug)
        ->back()->keys($card.' [data-vani-price-for]', 'Enter')
        ->assertPathIs('/tai-khoan/dang-nhap')
        ->assertNoJavaScriptErrors();
});

it('trang chủ mặc định và trang chủ có khối thương hiệu đều dành kích thước cho logo', function (bool $configured) {
    T::seed(function () use ($configured): void {
        $library = app(MediaLibrary::class);
        $wideLogo = $library->store(UploadedFile::fake()->image('wide.jpg', 600, 180));
        $tallLogo = $library->store(UploadedFile::fake()->image('tall.jpg', 180, 600));
        $this->brand->update(['name' => 'Logo ngang', 'logo_media_id' => $wideLogo->id]);
        Brand::factory()->create(['name' => 'Logo dọc', 'logo_media_id' => $tallLogo->id]);
        if ($configured) {
            app(BuilderEngine::class)->save(BuilderEngine::HOME, [['type' => 'brand_grid', 'config' => ['title' => 'Thương hiệu mùa này']]]);
        }
    });

    $page = visit('/')->on()->mobile()
        ->assertVisible('main img[alt="Logo ngang"]')
        ->assertVisible('main img[alt="Logo dọc"]')
        ->assertNoJavaScriptErrors();
    $metrics = mobilePageMetrics($page);

    expect($metrics['unsized'])->toBe([])
        ->and($metrics['overflow'])->toBeLessThanOrEqual(0)
        ->and($metrics['js_kb'])->toBeLessThanOrEqual(MOBILE_JS_BUDGET_KB)
        ->and($metrics['css_kb'])->toBeLessThanOrEqual(MOBILE_CSS_BUDGET_KB);
})->with(['trang mặc định' => [false], 'trang dựng bằng khối' => [true]]);

it('bàn phím có thể bỏ qua điều hướng để vào nội dung chính trên desktop', function () {
    visit('/')->keys('css=body', 'Tab')
        ->assertScript('document.activeElement.getAttribute("href")', '#main-content')
        ->keys('a[href="#main-content"]', 'Enter')
        ->assertScript('document.activeElement.id', 'main-content')
        ->assertNoJavaScriptErrors();
});

it('mobile Back và Forward khôi phục bộ lọc, kết quả rỗng và từ khoá trên cùng trang', function () {
    $page = visit('/tim-kiem?q=lua&sort=newest&opt%5Bsize%5D=XL')->on()->mobile()
        ->assertSee('Không có sản phẩm phù hợp.')
        ->click('Bỏ tất cả bộ lọc')
        ->waitForText('Đầm lụa')
        ->assertQueryStringHas('q', 'lua')
        ->assertQueryStringHas('sort', 'newest');

    $page->back()->waitForText('Không có sản phẩm phù hợp.')
        ->assertScript("new URLSearchParams(location.search).get('opt[size]')", 'XL')
        ->assertSee('Bỏ tất cả bộ lọc')
        ->forward()->waitForText('Đầm lụa')
        ->assertNoJavaScriptErrors();
});

it('gửi hai thao tác lọc liên tiếp: chỉ kết quả mới nhất được ghi vào history', function () {
    $page = visit('/tim-kiem?q=lua&opt%5Bsize%5D=XL&brand=lumiere')->on()->mobile()
        ->assertSee('Không có sản phẩm phù hợp.');
    $page->script(<<<'JS'
        () => {
            const originalFetch = window.fetch;
            const listing = document.querySelector('[data-vani-listing]');
            const before = history.length;
            let releaseFirstResponse;
            const firstResponse = new Promise((resolve) => { releaseFirstResponse = resolve; });
            let reportFirstRequest;
            window.firstListingAborted = new Promise((resolve) => { reportFirstRequest = resolve; });
            let requestCount = 0;
            window.fetch = async (url, options) => {
                if (!new URL(url, location.href).searchParams.has('q') || ++requestCount !== 1) return originalFetch(url, options);
                await firstResponse;
                try {
                    const response = await originalFetch(url, options);
                    reportFirstRequest(false);
                    return response;
                } catch (error) {
                    reportFirstRequest(error.name === 'AbortError');
                    throw error;
                }
            };
            document.addEventListener('vani:content-updated', () => {
                releaseFirstResponse();
                window.laterListingWon = history.length === before + 1 && location.search === '?q=lua';
            });
            listing.querySelector('a[aria-label^="Bỏ lọc"]').click();
            [...listing.querySelectorAll('a')].find((link) => link.textContent === 'Bỏ tất cả bộ lọc').click();
        }
    JS);

    $page->waitForText('Đầm lụa')
        ->assertScript('window.firstListingAborted', true)
        ->assertScript('window.laterListingWon', true)
        ->assertNoJavaScriptErrors();
});

it('sản phẩm dưới màn hình đầu chờ hiệu ứng và hiện ra khi cuộn tới', function () {
    foreach (range(1, 9) as $index) {
        T::product(null, ['name' => "Đầm {$index}", 'category_ids' => [$this->category->id]]);
    }
    $page = visit('/danh-muc/dam')->on()->mobile();
    expect($page->script("() => document.querySelectorAll('[data-vani-motion-pending]').length"))->toBeGreaterThan(0);

    $page->script('() => window.scrollTo(0, document.body.scrollHeight)');
    $page->wait(1)
        ->assertScript("document.querySelectorAll('[data-vani-motion-pending]').length", 0)
        ->assertNoJavaScriptErrors();
});

it('bộ sưu tập nổi bật vừa màn hình nhỏ và dẫn tới đúng tập sản phẩm', function () {
    T::seed(function (): void {
        app(PluginManager::class)->install(StorefrontExtrasServiceProvider::ID);
        app(PluginManager::class)->enable(StorefrontExtrasServiceProvider::ID);
        app()->register(StorefrontExtrasServiceProvider::class);
        $collection = ProductCollection::query()->create(['slug' => 'mua-he', 'status' => 'active']);
        $collection->syncTranslations(['vi' => ['name' => 'Bộ sưu tập mùa hè']]);
        $collection->styles()->attach($this->product->id);
        $cover = app(MediaLibrary::class)->store(UploadedFile::fake()->image('collection.jpg', 800, 1000));
        app(CollectionPages::class)->save($collection->id, [
            ['type' => 'hero', 'zone' => 'banner', 'config' => ['title' => 'Bộ sưu tập mùa hè', 'image' => $cover->id]],
            ['type' => 'rich_text', 'zone' => 'before', 'config' => ['body' => '<p>Chất liệu cho ngày nắng</p>']],
            ['type' => 'rich_text', 'zone' => 'after', 'config' => ['body' => '<p>Gợi ý phối đồ</p>']],
        ], [$cover->id]);
        app(BuilderEngine::class)->save(BuilderEngine::HOME, [['type' => 'extras_featured_collection', 'config' => [
            'collection' => (string) $collection->id,
            'image' => $cover->id,
            'image_alt' => 'Ảnh bộ sưu tập mùa hè',
        ]]]);
    });

    $page = visit('/')->resize(320, 844)->assertVisible('[data-block="extras_featured_collection"] img[alt="Ảnh bộ sưu tập mùa hè"]');
    expect(mobilePageMetrics($page)['overflow'])->toBeLessThanOrEqual(0);
    $page->resize(1280, 844)->assertSee('Bộ sưu tập mùa hè');
    expect(mobilePageMetrics($page)['overflow'])->toBeLessThanOrEqual(0);
    $page->click('Xem bộ sưu tập')
        ->assertPathIs('/bo-suu-tap/mua-he')
        ->assertVisible('[data-collection-banner] img')
        ->assertVisible('[data-vani-product="'.$this->product->slug.'"]')
        ->assertSee('Chất liệu cho ngày nắng')
        ->assertSee('Gợi ý phối đồ')
        ->assertNoJavaScriptErrors();
    $page->resize(320, 844);
    $metrics = mobilePageMetrics($page);
    expect($metrics['overflow'])->toBeLessThanOrEqual(0)
        ->and($metrics['unsized'])->toBe([])
        ->and($metrics['lazy_first_screen'])->toBe([]);
});
