<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Contracts\ProductViewStatistics;
use Modules\Catalog\Events\ProductViewed;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s, 'brand' => $this->brand] = C::store();
    $this->product = $this->s->product;
});

it('beacon lượt xem (Storefront API, ADR-044): miễn CSRF, 204, gom theo giờ, lọc trùng cùng phiên 30 phút, phát ProductViewed; mẫu ẩn bị bỏ', function () {
    $this->useArrayCache(); // travel() qua khoảng lọc trùng 30 phút
    Event::fake([ProductViewed::class]);

    $this->post('/api/storefront/v1/products/views', ['product_id' => $this->product->id], ['Sec-Fetch-Site' => 'same-origin'])->assertNoContent();
    $this->post('/api/storefront/v1/products/views', ['product_id' => $this->product->id], ['Sec-Fetch-Site' => 'same-origin'])->assertNoContent();
    expect(DB::table('product_view_stats')->where('product_id', $this->product->id)->sum('views'))->toEqual(1);
    Event::assertDispatchedTimes(ProductViewed::class, 1);

    $this->travel(31)->minutes();
    $this->post('/api/storefront/v1/products/views', ['product_id' => $this->product->id], ['Sec-Fetch-Site' => 'same-origin']);
    expect(app(ProductViewStatistics::class)->counts([$this->product->id]))->toBe([$this->product->id => 2]);

    $draft = T::product($this->brand->id, ['status' => 'draft']);
    $this->post('/api/storefront/v1/products/views', ['product_id' => $draft->id], ['Sec-Fetch-Site' => 'same-origin'])->assertNoContent();
    expect(DB::table('product_view_stats')->where('product_id', $draft->id)->exists())->toBeFalse();
});

it('beacon cùng IP phân biệt trình duyệt và chỉ tin viewer_id gửi cùng nguồn', function () {
    $headers = ['Sec-Fetch-Site' => 'same-origin', 'User-Agent' => 'Same Browser'];
    $url = '/api/storefront/v1/products/views';
    $send = fn (string $id, array $headers) => $this->post($url, ['product_id' => $this->product->id, 'viewer_id' => $id], $headers)->assertNoContent();

    $send(str_repeat('a', 32), $headers);
    $send(str_repeat('a', 32), $headers);
    $send(str_repeat('b', 32), $headers);
    expect(app(ProductViewStatistics::class)->counts([$this->product->id]))->toBe([$this->product->id => 2]);

    $headers['Sec-Fetch-Site'] = 'cross-site';
    $send(str_repeat('c', 32), $headers);
    $send(str_repeat('d', 32), $headers);
    expect(app(ProductViewStatistics::class)->counts([$this->product->id]))->toBe([$this->product->id => 3]);
});

it('HTML cache CDN dùng chung và beacon cùng nguồn vẫn nhận khách từ phiên đăng nhập', function () {
    config(['vanishop.storefront.page_cache.enabled' => true]);
    $customer = H::account();
    $this->post('/tai-khoan/dang-nhap', ['email' => $customer->email, 'password' => H::PASSWORD])->assertRedirect();
    $this->get('/san-pham/'.$this->product->slug)->assertOk()->assertHeaderMissing('Set-Cookie');
    Event::fake([ProductViewed::class]);

    $this->post('/api/storefront/v1/products/views', [
        'product_id' => $this->product->id,
        'viewer_id' => str_repeat('a', 32),
    ], ['Sec-Fetch-Site' => 'same-origin'])->assertNoContent();
    Event::assertDispatched(ProductViewed::class, fn (ProductViewed $event): bool => $event->customerId === $customer->id);
});

it('thống kê: tổng theo khoảng ngày, xem nhiều nhất', function () {
    $other = T::product($this->brand->id, ['name' => 'Áo khác']);
    DB::table('product_view_stats')->insert([
        ['product_id' => $this->product->id, 'hour' => now()->utc()->subDays(2)->startOfHour(), 'views' => 3],
        ['product_id' => $this->product->id, 'hour' => now()->utc()->subDays(40)->startOfHour(), 'views' => 100],
        ['product_id' => $other->id, 'hour' => now()->utc()->subHour()->startOfHour(), 'views' => 5],
    ]);

    expect(app(ProductViewStatistics::class)->top(30, 5))->toBe([$other->id => 5, $this->product->id => 3])
        ->and(app(ProductViewStatistics::class)->counts([$this->product->id], 60))->toBe([$this->product->id => 103]);
});

it('PDP có khung "Đã xem gần đây" + beacon (HTML không phụ thuộc khách); Admin hiện lượt xem 30 ngày', function () {
    $this->get('/san-pham/'.$this->product->slug)->assertOk()
        ->assertSee('data-vani-recently-viewed', false)
        ->assertSee('data-view-url="'.route('api.storefront.v1.products.views').'"', false);

    DB::table('product_view_stats')->insert(['product_id' => $this->product->id, 'hour' => now()->utc()->startOfHour(), 'views' => 7]);
    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff')
        ->get('/admin/catalog/products')->assertOk()->assertInertia(fn (Assert $page) => $page->where('products.data.0.views', 7));
});
