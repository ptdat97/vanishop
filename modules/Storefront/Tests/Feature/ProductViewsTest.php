<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Contracts\ProductViewStatistics;
use Modules\Catalog\Events\ProductViewed;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s, 'brand' => $this->brand] = C::store();
    $this->style = $this->s->style;
});

it('beacon lượt xem (Storefront API, ADR-044): miễn CSRF, 204, gom theo giờ, lọc trùng cùng phiên 30 phút, phát ProductViewed; mẫu ẩn bị bỏ', function () {
    Event::fake([ProductViewed::class]);

    $this->post('/api/storefront/v1/products/views', ['style_id' => $this->style->id], ['Sec-Fetch-Site' => 'same-origin'])->assertNoContent();
    $this->post('/api/storefront/v1/products/views', ['style_id' => $this->style->id], ['Sec-Fetch-Site' => 'same-origin'])->assertNoContent();
    expect(DB::table('style_view_stats')->where('style_id', $this->style->id)->sum('views'))->toEqual(1);
    Event::assertDispatchedTimes(ProductViewed::class, 1);

    $this->travel(31)->minutes();
    $this->post('/api/storefront/v1/products/views', ['style_id' => $this->style->id], ['Sec-Fetch-Site' => 'same-origin']);
    expect(app(ProductViewStatistics::class)->counts([$this->style->id]))->toBe([$this->style->id => 2]);

    $draft = T::product($this->brand->id, ['status' => 'draft']);
    $this->post('/api/storefront/v1/products/views', ['style_id' => $draft->id], ['Sec-Fetch-Site' => 'same-origin'])->assertNoContent();
    expect(DB::table('style_view_stats')->where('style_id', $draft->id)->exists())->toBeFalse();
});

it('thống kê: tổng theo khoảng ngày, xem nhiều nhất', function () {
    $other = T::product($this->brand->id, ['name' => 'Áo khác']);
    DB::table('style_view_stats')->insert([
        ['style_id' => $this->style->id, 'hour' => now()->utc()->subDays(2)->startOfHour(), 'views' => 3],
        ['style_id' => $this->style->id, 'hour' => now()->utc()->subDays(40)->startOfHour(), 'views' => 100],
        ['style_id' => $other->id, 'hour' => now()->utc()->subHour()->startOfHour(), 'views' => 5],
    ]);

    expect(app(ProductViewStatistics::class)->top(30, 5))->toBe([$other->id => 5, $this->style->id => 3])
        ->and(app(ProductViewStatistics::class)->counts([$this->style->id], 60))->toBe([$this->style->id => 103]);
});

it('PDP có khung "Đã xem gần đây" + beacon (HTML không phụ thuộc khách); Admin hiện lượt xem 30 ngày', function () {
    $this->get('/san-pham/'.$this->style->slug)->assertOk()
        ->assertSee('data-vani-recently-viewed', false)
        ->assertSee('data-view-url="'.route('api.storefront.v1.products.views').'"', false);

    DB::table('style_view_stats')->insert(['style_id' => $this->style->id, 'hour' => now()->utc()->startOfHour(), 'views' => 7]);
    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff')
        ->get('/admin/catalog/products')->assertOk()->assertInertia(fn (Assert $page) => $page->where('products.data.0.views', 7));
});
