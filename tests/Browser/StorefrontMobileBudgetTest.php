<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Application\Products\VariantImages;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;

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
    ['s' => $this->s] = C::store();
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

it('màn 320px (iPhone SE): trang danh mục và sản phẩm không cuộn ngang', function () {
    foreach (['/danh-muc/dam', "/san-pham/{$this->product->slug}"] as $path) {
        expect(mobilePageMetrics(visit($path)->on()->iPhoneSE())['overflow'])->toBeLessThanOrEqual(0, "{$path}: cuộn ngang");
    }
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
