<?php

use App\Livewire\Pulse\CommerceMetricsCard;
use App\Observability\HealthCheck;
use App\Observability\MetricsSnapshot;
use App\Observability\PulseMetrics;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Pulse\Facades\Pulse;
use Livewire\Livewire;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;
use Modules\Shared\Contracts\Metrics;

require_once __DIR__.'/../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Phase 6 observability: metric tối thiểu (counter từ nghiệp vụ, gauge chụp định kỳ), thẻ Pulse, GET /health.
*/

final class RecordingMetrics implements Metrics
{
    /** @var list<array{0: string, 1: string, 2: string, 3: int}> */
    public array $recorded = [];

    public function increment(string $name, int $by = 1, string $key = 'all'): void
    {
        $this->recorded[] = ['counter', $name, $key, $by];
    }

    public function gauge(string $name, int $value, string $key = 'all'): void
    {
        $this->recorded[] = ['gauge', $name, $key, $value];
    }

    public function sum(string $name, ?string $key = null): int
    {
        return array_sum(array_map(fn (array $row): int => $row[3], array_filter($this->recorded, fn (array $row): bool => $row[0] === 'counter' && $row[1] === $name && ($key === null || $row[2] === $key))));
    }
}

beforeEach(function () {
    app(Extensions::class)->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    $this->metrics = new RecordingMetrics;
    $this->app->instance(Metrics::class, $this->metrics);
    ['s' => $this->s] = C::store(stock: 2);
    $this->place = function (int $quantity, string $method, string $key, int $expected) {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("/api/storefront/v1/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => $quantity], $headers);

        return $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => $expected]), [...$headers, 'Idempotency-Key' => $key]);
    };
});

it('counter từ nghiệp vụ: đơn tạo, checkout bị từ chối theo mã lỗi, thanh toán thất bại', function () {
    ($this->place)(1, 'cod', 'metrics-order-0001', 330_000)->assertCreated();
    ($this->place)(1, 'cod', 'metrics-order-0002', 999)->assertStatus(409); // tổng tiền đổi
    $payment = ($this->place)(1, 'fake_online', 'metrics-order-0003', 330_000)->assertCreated()->json('data.payment');
    $this->postJson('/api/payments/fake_online/callback', FakeOnlineGateway::callbackPayload($payment['id'], 'TXN-METRIC-1', 'failed', 330_000))->assertOk();

    expect($this->metrics->sum('orders.created'))->toBe(2)
        ->and($this->metrics->sum('orders.failed', 'checkout.totals_changed'))->toBe(1)
        ->and($this->metrics->sum('payments.failed', 'fake_online'))->toBe(1);
});

it('gauge chụp định kỳ: thanh toán chờ theo cổng, outbox tồn, plugin còn giao dịch dở; ghi nhịp scheduler', function () {
    ($this->place)(1, 'cod', 'metrics-order-0004', 330_000)->assertCreated();
    ($this->place)(1, 'fake_online', 'metrics-order-0005', 330_000)->assertCreated();

    $values = app(MetricsSnapshot::class)->capture();
    expect($values)->toMatchArray(['payments.pending' => 2, 'payments.pending:cod' => 1, 'payments.pending:fake_online' => 1, 'plugin.active_transactions:vani.cod' => 1])
        ->and($values)->toHaveKey('integration.outbox_backlog');

    $this->artisan('vani:metrics:snapshot')->expectsOutputToContain('payments.pending = 2')->assertSuccessful();
    expect(Cache::get(HealthCheck::HEARTBEAT_KEY))->toBeInt();
});

it('PulseMetrics ghi vào Pulse; thẻ "Thương mại" hiện trên dashboard Pulse', function () {
    $this->app->forgetInstance(Metrics::class);
    $this->app->singleton(Metrics::class, PulseMetrics::class);
    Pulse::startRecording(); // test tắt Pulse (PULSE_ENABLED=false) — bật ghi riêng cho test này
    app(Metrics::class)->increment('orders.created', 3);
    app(Metrics::class)->gauge('payments.pending', 7);
    Pulse::ingest();
    Pulse::stopRecording();

    expect((int) DB::table('pulse_aggregates')->where('type', 'vani.orders.created')->where('aggregate', 'sum')->where('period', 60)->sum('value'))->toBe(3)
        ->and(DB::table('pulse_values')->where('type', 'vani.payments.pending')->value('value'))->toBe('7');

    $staff = T::staff(['admin.access', 'system.monitor']);
    $this->actingAs($staff, 'staff')->get('/admin/system/pulse')->assertOk()->assertSee('vani.commerce-metrics', false);
    Livewire::withoutLazyLoading()->test(CommerceMetricsCard::class)
        ->assertSee('Thương mại')->assertSee('Đơn tạo')->assertSee('Thanh toán đang chờ');
});

it('GET /health: status tổng hợp; chi tiết chỉ khi đúng token; thiếu extension bắt buộc → 503', function () {
    config(['vanishop.health.token' => 'secret-health-token']);
    Cache::put(HealthCheck::HEARTBEAT_KEY, time(), 60);

    $this->getJson('/health')->assertOk()->assertExactJson(['status' => 'ok']);
    $this->getJson('/health', ['X-Health-Token' => 'sai'])->assertOk()->assertJsonMissingPath('checks');
    $this->getJson('/health', ['X-Health-Token' => 'secret-health-token'])->assertOk()
        ->assertJsonPath('checks.database.status', 'ok')->assertJsonPath('checks.required_extensions.status', 'ok')->assertJsonPath('checks.scheduler.status', 'ok');

    Cache::forget(HealthCheck::HEARTBEAT_KEY);
    $this->getJson('/health')->assertOk()->assertJsonPath('status', 'degraded');

    DB::table('plugins')->where('id', 'vani.shipping-flat-rate')->update(['status' => 'disabled']); // phí giao duy nhất
    $this->getJson('/health', ['X-Health-Token' => 'secret-health-token'])->assertStatus(503)
        ->assertJsonPath('status', 'fail')->assertJsonPath('checks.required_extensions.status', 'fail');
});
