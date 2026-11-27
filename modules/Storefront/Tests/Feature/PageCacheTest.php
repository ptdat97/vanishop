<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Pricing\Contracts\PriceListSchedule;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Application\PageCache\BatchedPageCacheInvalidator;
use Modules\Shared\Contracts\PageCacheTags;
use Modules\Shared\Events\PageCacheTagsInvalidated;
use Modules\Tenancy\Contracts\Settings;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| Cache trang trong ứng dụng (ADR-051, storefront §5): HIT/MISS/BYPASS, xoá theo tag trong lô mỗi phút, xoá ngay, hạn
| theo lịch bảng giá, khoá cache, không lộ dữ liệu riêng của khách.
*/

beforeEach(function () {
    config(['vanishop.storefront.page_cache.mode' => 'app']);
    H::fakeOtp();
    ['s' => $this->s, 'brand' => $this->brand] = C::store();
    $this->product = '/san-pham/'.$this->s->product->slug;
    // Cron đang chạy: request không tự chạy lô xoá.
    Cache::put(BatchedPageCacheInvalidator::PROCESSED_AT_KEY, now()->getTimestamp(), 3600);
    $this->runBatch = fn () => $this->artisan('vani:page-cache:invalidate')->assertSuccessful();
    // Bỏ tag do dữ liệu mẫu vừa tạo.
    ($this->runBatch)();
});

it('lượt đầu MISS, lượt sau HIT cùng HTML, không truy vấn DB; không Set-Cookie, không lộ trạng thái khách đã đăng nhập', function () {
    $first = $this->get($this->product)->assertOk()->assertHeader('X-Vani-Cache', 'MISS')->assertHeader('Cache-Control', 'no-store, private');
    $this->post('/tai-khoan/dang-nhap', ['email' => H::account('0912345678')->email, 'password' => H::PASSWORD]);
    DB::enableQueryLog();
    $second = $this->get($this->product)->assertOk()->assertHeader('X-Vani-Cache', 'HIT');

    expect(DB::getQueryLog())->toBe([])
        ->and($second->getContent())->toBe($first->getContent())
        ->and($second->headers->getCookies())->toBe([])
        ->and($first->getContent())->not->toContain('name="_token"');
});

it('tham số theo dõi không tạo bản cache riêng; query khác thứ tự dùng chung một bản', function () {
    $this->get('/thuong-hieu?page=1&sort=newest')->assertHeader('X-Vani-Cache', 'MISS');

    $this->get('/thuong-hieu?sort=newest&page=1&utm_source=zalo&fbclid=x')->assertHeader('X-Vani-Cache', 'HIT');
});

it('không lưu: tìm kiếm có từ khoá, quá nhiều giá trị lọc, 404, link xem trước, khối đánh dấu không cache', function () {
    $this->get('/tim-kiem?q=dam')->assertOk()->assertHeader('X-Vani-Cache', 'BYPASS')->assertHeader('X-Vani-Cache-Reason', 'search');
    $this->get('/thuong-hieu?brand[]=a&brand[]=b&brand[]=c&opt[size][]=s&opt[size][]=m')->assertHeader('X-Vani-Cache-Reason', 'filters');
    $this->get('/san-pham/khong-ton-tai')->assertNotFound()->assertHeader('X-Vani-Cache-Reason', 'status');
    $this->get($this->product.'?signature=x')->assertHeader('X-Vani-Cache-Reason', 'preview');

    View::composer('theme::layouts.app', fn () => app(PageCacheTags::class)->uncacheable('geo'));
    $this->get('/thuong-hieu')->assertHeader('X-Vani-Cache', 'BYPASS')->assertHeader('X-Vani-Cache-Reason', 'geo');
    $this->get('/thuong-hieu')->assertHeader('X-Vani-Cache', 'BYPASS');
});

it('đổi giá: trang cũ tới khi lô xoá chạy, sau lô thấy giá mới; plugin CDN nhận tag đã xoá', function () {
    $sale = P::priceList(['type' => 'sale', 'priority' => 10, 'status' => 'inactive'], [$this->s->id => [250_000]]);
    $this->get($this->product)->assertSee('300.000');

    app(PriceListSchedule::class)->schedule($sale->id, null, null, true, 'sale');
    $this->get($this->product)->assertHeader('X-Vani-Cache', 'HIT')->assertDontSee('250.000');

    Event::fake([PageCacheTagsInvalidated::class]);
    ($this->runBatch)();
    Event::assertDispatched(PageCacheTagsInvalidated::class, fn (PageCacheTagsInvalidated $event): bool => in_array('product:'.$this->s->product_id, $event->tags, true));
});

it('sau lô xoá, trang có sản phẩm đổi giá render lại với giá mới', function () {
    $sale = P::priceList(['type' => 'sale', 'priority' => 10, 'status' => 'inactive'], [$this->s->id => [250_000]]);
    $this->get($this->product);

    app(PriceListSchedule::class)->schedule($sale->id, null, null, true, 'sale');
    ($this->runBatch)();

    $this->get($this->product)->assertHeader('X-Vani-Cache', 'MISS')->assertSee('250.000');
});

it('lưu Builder trang chủ: chỉ trang chủ render lại, trang sản phẩm vẫn HIT', function () {
    $this->get('/');
    $this->get($this->product);

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'storefront.manage'])->create(), 'staff')
        ->put('/admin/storefront/builder/home', ['blocks' => [['type' => 'rich_text', 'config' => ['body' => 'Khối mới tinh']]]])->assertSessionHasNoErrors();
    ($this->runBatch)();

    $this->get('/')->assertHeader('X-Vani-Cache', 'MISS')->assertSee('Khối mới tinh');
    $this->get($this->product)->assertHeader('X-Vani-Cache', 'HIT');
});

it('bảng giá hẹn giờ: bản cache hết hạn đúng lúc sale bắt đầu', function () {
    P::priceList(['type' => 'sale', 'priority' => 10, 'starts_at' => now()->addMinutes(10)], [$this->s->id => [250_000]]);
    $this->get($this->product)->assertHeader('X-Vani-Cache', 'MISS')->assertDontSee('250.000');
    $this->get($this->product)->assertHeader('X-Vani-Cache', 'HIT');

    $this->travel(11)->minutes();
    Cache::put(BatchedPageCacheInvalidator::PROCESSED_AT_KEY, now()->getTimestamp(), 3600);

    $this->get($this->product)->assertHeader('X-Vani-Cache', 'MISS')->assertSee('250.000');
});

it('đổi tên thương hiệu: trang thương hiệu render lại sau lô xoá', function () {
    $this->get('/thuong-hieu/'.$this->brand->slug)->assertOk();

    $this->brand->update(['name' => 'Tên Mới Của Hãng']);
    ($this->runBatch)();

    $this->get('/thuong-hieu/'.$this->brand->slug)->assertHeader('X-Vani-Cache', 'MISS')->assertSee('Tên Mới Của Hãng');
});

it('đổi chế độ hiện giá xoá ngay toàn bộ, không chờ lô', function () {
    $this->get($this->product)->assertSee('300.000');

    app(Settings::class)->set('core', 'storefront.price_visibility', 'members');

    $this->get($this->product)->assertHeader('X-Vani-Cache', 'MISS')->assertDontSee('300.000');
});

it('không có cron: request thấy lô cuối quá hạn thì tự chạy lô sau khi trả response', function () {
    $sale = P::priceList(['type' => 'sale', 'priority' => 10, 'status' => 'inactive'], [$this->s->id => [250_000]]);
    $this->get($this->product);
    app(PriceListSchedule::class)->schedule($sale->id, null, null, true, 'sale');
    Cache::put(BatchedPageCacheInvalidator::PROCESSED_AT_KEY, now()->getTimestamp() - 600, 3600);

    $this->get($this->product)->assertHeader('X-Vani-Cache', 'HIT');

    $this->get($this->product)->assertHeader('X-Vani-Cache', 'MISS')->assertSee('250.000');
});

it('Admin → Giao diện → Xoá cache trang: cần quyền storefront.manage, xoá ngay mọi trang, có audit', function () {
    $this->get('/thuong-hieu');

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access'])->create(), 'staff')
        ->post('/admin/storefront/page-cache/clear')->assertForbidden();
    $this->get('/thuong-hieu')->assertHeader('X-Vani-Cache', 'HIT');

    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'storefront.manage'])->create(), 'staff')
        ->post('/admin/storefront/page-cache/clear')->assertRedirect();

    $this->get('/thuong-hieu')->assertHeader('X-Vani-Cache', 'MISS');
    expect(AuditLog::query()->where('action', 'storefront.page_cache_cleared')->exists())->toBeTrue();
});

it('chế độ cdn: header public cho CDN kèm danh sách tag; chế độ off: có phiên, không cache', function () {
    config(['vanishop.storefront.page_cache.mode' => 'cdn']);
    $tags = $this->get($this->product)->assertHeader('X-Vani-Cache', 'MISS')
        ->assertHeader('Cache-Control', 'max-age=0, public, s-maxage=300, stale-while-revalidate=600')->headers->get('Cache-Tag');
    expect(explode(',', (string) $tags))->toContain('product:'.$this->s->product_id, '*');

    config(['vanishop.storefront.page_cache.mode' => 'off']);
    $response = $this->get($this->product)->assertOk()->assertHeaderMissing('X-Vani-Cache');
    expect(collect($response->headers->getCookies())->map->getName()->all())->toContain(config('session.cookie'));
});
