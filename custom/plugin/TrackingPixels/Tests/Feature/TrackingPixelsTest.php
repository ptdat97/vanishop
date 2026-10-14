<?php

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Identity\Persistence\Models\StaffUser;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;
use Plugin\TrackingPixels\Infrastructure\SendServerPurchase;
use Plugin\TrackingPixels\Infrastructure\TrackingSettings;
use Plugin\TrackingPixels\TrackingPixelsServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s] = C::store();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(TrackingPixelsServiceProvider::ID);
        app(PluginManager::class)->enable(TrackingPixelsServiceProvider::ID);
    });
    app()->register(TrackingPixelsServiceProvider::class);
    $this->configure = fn (array $values) => app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($values) {
        foreach ($values as $key => $value) {
            app(Settings::class)->set(TrackingPixelsServiceProvider::ID, $key, $value);
        }
    });
    $this->product = fn () => $this->get('/san-pham/'.$this->s->style->slug)->assertOk()->getContent();
    $this->placeOrder = function (array $cookies = []) {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("/api/storefront/v1/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

        return $this->withCredentials()->withUnencryptedCookies($cookies)->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => "tp-{$cart}"])->assertCreated();
    };
});

/**
 * @return array<string, array<string, mixed>> tên sự kiện => dữ liệu
 */
function trackedEvents(string $html): array
{
    preg_match_all('/<script type="application\/json" data-vani-track="([a-z_]+)">(.*?)<\/script>/s', $html, $matches, PREG_SET_ORDER);

    return collect($matches)->mapWithKeys(fn (array $match): array => [$match[1] => json_decode($match[2], true)])->all();
}

it('chưa cấu hình mã nào → không có script hay sự kiện', function () {
    expect(($this->product)())->not->toContain('vani-tracking-config')->not->toContain('data-vani-track')->not->toContain('data-vani-consent');
});

it('có mã: trang sản phẩm có cấu hình + view_item đúng giá; danh sách/tìm kiếm có view_item_list, search; thanh đồng ý mặc định', function () {
    ($this->configure)(['meta_pixel_id' => '123456789012345', 'ga4_measurement_id' => 'G-TEST1234']);

    $html = ($this->product)();
    expect($html)->toContain('id="vani-tracking-config"')->toContain('"meta":"123456789012345"')->toContain('"consent":"banner"')->toContain('data-vani-consent')
        ->and(trackedEvents($html)['view_item'])->toMatchArray(['currency' => 'VND', 'value' => 300000])
        ->and(trackedEvents($html)['view_item']['items'][0])->toMatchArray(['item_id' => $this->s->sku, 'price' => 300000, 'quantity' => 1]);

    $search = trackedEvents($this->get('/tim-kiem?q=lua')->assertOk()->getContent());
    expect($search['search'])->toBe(['search_term' => 'lua'])->and($search)->toHaveKey('view_item_list');
});

it('trang thanh toán có begin_checkout; trang đơn có purchase với mã sự kiện, không lộ email/SĐT trong dữ liệu sự kiện', function () {
    ($this->configure)(['ga4_measurement_id' => 'G-TEST1234', 'consent_mode' => 'none']);
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    expect(trackedEvents($this->get('/thanh-toan')->assertOk()->getContent())['begin_checkout']['items'][0])->toMatchArray(['item_id' => $this->s->sku, 'quantity' => 1]);

    $placed = ($this->placeOrder)();
    $order = Order::query()->withoutGlobalScopes()->latest('id')->first();
    $html = $this->withSession(["vani.orders.{$order->public_id}" => ['token' => $placed->json('data.access_token')]])->get("/don-hang/{$order->public_id}")->assertOk()->getContent();
    $purchase = trackedEvents($html)['purchase'];

    expect($purchase)->toMatchArray(['transaction_id' => $order->number, 'event_id' => "purchase-{$order->public_id}", 'currency' => 'VND', 'value' => 330000, 'payment_type' => 'cod'])
        ->and(json_encode($purchase))->not->toContain('Lan@Example.com')->not->toContain('0912');
});

it('server-side: chỉ gửi khi bật và khách đã đồng ý cookie; job băm email/SĐT, cùng mã sự kiện với trình duyệt', function () {
    Queue::fake();
    ($this->configure)(['meta_pixel_id' => '123456789012345', 'meta_capi_enabled' => true, 'meta_access_token' => 'tok', 'ga4_measurement_id' => 'G-TEST1234', 'ga4_mp_enabled' => true, 'ga4_api_secret' => 'sec']);

    ($this->placeOrder)();
    Queue::assertNotPushed(SendServerPurchase::class); // chưa đồng ý (chế độ thanh đồng ý)

    ($this->placeOrder)([TrackingSettings::CONSENT_COOKIE => 'granted', '_ga' => 'GA1.1.111.222', '_fbp' => 'fb.1.2.3']);
    $order = Order::query()->withoutGlobalScopes()->latest('id')->first();
    Queue::assertPushed(SendServerPurchase::class, fn (SendServerPurchase $job): bool => $job->meta && $job->ga4
        && $job->purchase['event_id'] === "purchase-{$order->public_id}" && $job->purchase['browser']['fbp'] === 'fb.1.2.3');

    Http::fake(['graph.facebook.com/*' => Http::response(['events_received' => 1]), 'www.google-analytics.com/*' => Http::response('', 204)]);
    /** @var SendServerPurchase $job */
    $job = Queue::pushed(SendServerPurchase::class)->first();
    $job->handle(app(TrackingSettings::class));

    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'graph.facebook.com/v19.0/123456789012345/events')
        && $request['data'][0]['user_data']['em'] === [hash('sha256', 'lan@example.com')]
        && $request['data'][0]['user_data']['ph'] === [hash('sha256', '84912345678')]
        && $request['data'][0]['event_id'] === "purchase-{$order->public_id}" && $request['access_token'] === 'tok');
    Http::assertSent(fn ($request): bool => str_contains($request->url(), 'mp/collect') && $request['client_id'] === '111.222' && $request['events'][0]['params']['transaction_id'] === $order->number);
});

it('nền tảng lỗi: job ném lỗi để thử lại, hết lượt thì dừng không ném', function () {
    ($this->configure)(['ga4_measurement_id' => 'G-TEST1234', 'ga4_mp_enabled' => true, 'ga4_api_secret' => 'sec']);
    Http::fake(['*' => Http::response('', 500)]);
    $purchase = ['event_id' => 'purchase-x', 'event_time' => 1, 'source_url' => '', 'transaction_id' => 'VN1', 'currency' => 'VND', 'value' => 1, 'tax' => 0, 'shipping' => 0,
        'payment_type' => 'cod', 'items' => [], 'customer' => ['email' => null, 'phone' => null, 'id' => null], 'browser' => ['ip' => null, 'user_agent' => '', 'fbp' => null, 'fbc' => null, 'ga' => null]];

    $job = (new SendServerPurchase($purchase, false, true))->withFakeQueueInteractions();
    expect(fn () => $job->handle(app(TrackingSettings::class)))->toThrow(RuntimeException::class);

    $job->job->attempts = 3;
    $job->handle(app(TrackingSettings::class)); // lượt cuối: không ném
    Http::assertSentCount(2);
});

it('cấu hình: mã sai định dạng bị từ chối', function () {
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'settings.manage'])->create(), 'staff');

    $this->put('/admin/settings', ['namespace' => TrackingPixelsServiceProvider::ID, 'values' => ['ga4_measurement_id' => 'UA-123']])->assertSessionHasErrors('values.ga4_measurement_id');
    $this->put('/admin/settings', ['namespace' => TrackingPixelsServiceProvider::ID, 'values' => ['ga4_measurement_id' => 'G-ABCD1234']])->assertSessionHasNoErrors();
});
