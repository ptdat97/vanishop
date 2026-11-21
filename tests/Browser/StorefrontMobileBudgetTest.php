<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Media\MediaLibrary;
use Modules\Catalog\Application\Products\VariantImages;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Storefront\Application\PageBlocks;

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
        UploadedFile::fake()->image('sau.jpg', 600, 900),
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
                return rect.width > 0 && rect.top < window.innerHeight && rect.bottom > 0;
            };
            const images = [...document.images];
            return {
                overflow: document.documentElement.scrollWidth - window.innerWidth,
                js_kb: kb('.js'),
                css_kb: kb('.css'),
                unsized: images.filter((img) => !(img.getAttribute('width') && img.getAttribute('height'))).map((img) => img.src),
                lazy_first_screen: images.filter((img) => img.loading === 'lazy' && inFirstScreen(img)).map((img) => img.src),
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

it('màn 320px: trang chủ, danh mục và sản phẩm không cuộn ngang', function () {
    foreach (['/', '/danh-muc/dam', "/san-pham/{$this->product->slug}"] as $path) {
        $page = visit($path)->resize(320, 568)->assertNoJavaScriptErrors();
        expect(mobilePageMetrics($page)['overflow'])->toBeLessThanOrEqual(0, "{$path}: cuộn ngang");
    }
});

it('trang chủ mặc định và trang chủ có khối thương hiệu đều dành kích thước cho logo', function (bool $configured) {
    T::seed(function () use ($configured): void {
        $library = app(MediaLibrary::class);
        $wideLogo = $library->store(UploadedFile::fake()->image('wide.jpg', 600, 180));
        $tallLogo = $library->store(UploadedFile::fake()->image('tall.jpg', 180, 600));
        $this->brand->update(['name' => 'Logo ngang', 'logo_media_id' => $wideLogo->id]);
        Brand::factory()->create(['name' => 'Logo dọc', 'logo_media_id' => $tallLogo->id]);
        if ($configured) {
            app(PageBlocks::class)->saveHome([['type' => 'brand_grid', 'config' => ['title' => 'Thương hiệu mùa này']]]);
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
