<?php

use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Shared\Support\LocalizedUrls;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['brand' => $this->brand] = C::store();
    $this->style = Style::query()->sole();
});

it('URL tiếng Việt giữ nguyên; tiếng Anh dưới /en với đường dẫn tiếng Anh', function () {
    expect(LocalizedUrls::route('storefront.product', $this->style->slug, 'vi'))->toBe(url("/san-pham/{$this->style->slug}"))
        ->and(LocalizedUrls::route('storefront.product', $this->style->slug, 'en'))->toBe(url("/en/products/{$this->style->slug}"))
        ->and(LocalizedUrls::route('storefront.account.addresses.edit', 5, 'en'))->toBe(url('/en/account/addresses/5/edit'))
        ->and(LocalizedUrls::route('storefront.home', [], 'en'))->toBe(url('/en'))
        // Route chỉ có ở ngôn ngữ mặc định → URL mặc định.
        ->and(LocalizedUrls::route('storefront.session', [], 'en'))->toBe(url('/_vani/phien'))
        ->and(LocalizedUrls::baseName('storefront.en.product'))->toBe('storefront.product');
});

it('ngôn ngữ lấy từ URL, không theo header; link trong trang theo ngôn ngữ của trang', function () {
    $html = $this->get('/en', ['X-Vani-Locale' => 'vi'])->assertOk()->getContent();
    expect($html)->toContain('<html lang="en"')
        ->toContain(url('/en/brands'))
        ->toContain(url("/en/products/{$this->style->slug}"))
        ->toContain(url('/_vani/phien?locale=en'))
        ->not->toContain(url("/san-pham/{$this->style->slug}"));

    $vi = $this->get('/', ['X-Vani-Locale' => 'en'])->assertOk()->getContent();
    expect($vi)->toContain('<html lang="vi"')->toContain(url("/san-pham/{$this->style->slug}"));

    $this->get("/en/products/{$this->style->slug}")->assertOk()->assertSee('<html lang="en"', false);
    $this->get("/en/brands/{$this->brand->slug}")->assertOk();
    $this->get('/en/search?q=lua')->assertOk();
    $this->get('/en/cart')->assertOk()->assertSee('<html lang="en"', false);
    $this->get('/en/account')->assertRedirect(url('/en/account/login'));
});

it('phiên của trang có cache trả link tài khoản theo ngôn ngữ của trang', function () {
    $this->getJson('/_vani/phien?locale=en')->assertOk()->assertJsonPath('account.url', url('/en/account/login'));
    $this->getJson('/_vani/phien?locale=xx')->assertOk()->assertJsonPath('account.url', url('/tai-khoan/dang-nhap'));
});

it('thêm vào giỏ ở trang tiếng Anh quay về giỏ tiếng Anh', function () {
    $variant = Variant::query()->first();
    $this->post('/en/cart', ['variant_id' => $variant->id, 'quantity' => 1])->assertRedirect(url('/en/cart'));
});
