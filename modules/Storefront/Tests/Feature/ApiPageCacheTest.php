<?php

use Illuminate\Support\Facades\Cache;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Pricing\Contracts\PriceListSchedule;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Application\PageCache\BatchedPageCacheInvalidator;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| GET công khai của Storefront API dùng chung cache trang (ADR-051 đợt 4, L60): chỉ khách vãng lai, xoá theo cùng tag,
| ETag + 304, CDN chỉ khi giá công khai.
*/

beforeEach(function () {
    config(['vanishop.storefront.page_cache.mode' => 'app']);
    H::fakeOtp();
    ['s' => $this->s] = C::store();
    $this->api = '/api/storefront/v1/products/'.$this->s->product->slug;
    Cache::put(BatchedPageCacheInvalidator::PROCESSED_AT_KEY, time(), 3600);
    $this->runBatch = fn () => $this->artisan('vani:page-cache:invalidate')->assertSuccessful();
    ($this->runBatch)();
});

it('khách vãng lai: MISS rồi HIT cùng nội dung; ETag khớp → 304 không thân', function () {
    $first = $this->getJson($this->api)->assertOk()->assertHeader('X-Vani-Cache', 'MISS')->assertHeader('Vary', 'Accept-Encoding, X-Vani-Locale');
    $second = $this->getJson($this->api)->assertOk()->assertHeader('X-Vani-Cache', 'HIT');

    expect($second->getContent())->toBe($first->getContent());
    $this->getJson($this->api, ['If-None-Match' => $first->headers->get('ETag')])->assertStatus(304)->assertContent('');
});

it('danh sách, danh mục, thương hiệu, vỏ trang dùng cache; gợi ý tìm kiếm thì không', function (string $path) {
    $this->getJson($path)->assertOk()->assertHeader('X-Vani-Cache', 'MISS');
    $this->getJson($path)->assertOk()->assertHeader('X-Vani-Cache', 'HIT');
})->with(['/api/storefront/v1/products', '/api/storefront/v1/categories', '/api/storefront/v1/brands', '/api/storefront/v1/store', '/api/storefront/v1/pages/home']);

it('gợi ý tìm kiếm và danh sách có từ khoá không lưu', function () {
    $this->getJson('/api/storefront/v1/search/suggestions?q=dam')->assertOk()->assertHeaderMissing('X-Vani-Cache');
    $this->getJson('/api/storefront/v1/products?q=dam')->assertOk()->assertHeader('X-Vani-Cache-Reason', 'search');
});

it('đổi giá: API thấy giá mới sau lô xoá, cùng tag với trang HTML', function () {
    $sale = P::priceList(['type' => 'sale', 'priority' => 10, 'status' => 'inactive'], [$this->s->id => [250_000]]);
    $this->getJson($this->api)->assertJsonPath('data.price.max.amount', 300_000);

    app(PriceListSchedule::class)->schedule($sale->id, null, null, true, 'sale');
    ($this->runBatch)();

    $this->getJson($this->api)->assertHeader('X-Vani-Cache', 'MISS')->assertJsonPath('data.price.max.amount', 250_000);
});

it('ẩn giá: khách Bearer và khách đăng nhập qua phiên cùng nguồn không dùng bản chung, vẫn thấy giá', function () {
    app(Settings::class)->set('core', 'storefront.price_visibility', 'members');
    $this->getJson($this->api)->assertHeader('X-Vani-Cache', 'MISS')->assertJsonPath('data.price', null);

    $this->getJson($this->api, H::auth(H::login($this)))->assertHeader('X-Vani-Cache-Reason', 'authorized')
        ->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('data.price.min.amount', 200_000);

    $this->post('/tai-khoan/dang-nhap', ['email' => H::account('0912345678')->email, 'password' => H::PASSWORD]);
    $this->getJson($this->api, ['Sec-Fetch-Site' => 'same-origin'])->assertHeader('X-Vani-Cache-Reason', 'signed_in')
        ->assertJsonPath('data.price.min.amount', 200_000);

    $this->getJson($this->api)->assertHeader('X-Vani-Cache', 'HIT')->assertJsonPath('data.price', null);
});

it('chế độ cdn: header public + tag khi giá công khai; ẩn giá thì CDN không cache; off thì không cache', function () {
    config(['vanishop.storefront.page_cache.mode' => 'cdn']);
    $this->getJson($this->api)->assertHeader('Cache-Control', 'max-age=0, public, s-maxage=300, stale-while-revalidate=600')
        ->assertHeader('Cache-Tag');

    app(Settings::class)->set('core', 'storefront.price_visibility', 'members');
    $this->getJson($this->api)->assertHeader('Cache-Control', 'no-store, private');

    config(['vanishop.storefront.page_cache.mode' => 'off']);
    $this->getJson($this->api)->assertOk()->assertHeaderMissing('X-Vani-Cache');
});
