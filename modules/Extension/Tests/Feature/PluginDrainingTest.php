<?php

use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Fulfillment\Tests\Feature\Fixtures\FakeApiCarrier;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Persistence\Models\Payment;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Plugin draining (roadmap Phase 5, 0.3.23): ngừng nhận giao dịch mới, vẫn xử lý giao dịch đang dở, tự tắt khi xong.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->cart = function () {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();

        return [$created->json('data.id'), $headers];
    };
    $this->place = function (string $method, string $key) {
        [$cart, $headers] = ($this->cart)();

        return $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => $key]);
    };
    $this->methods = function () {
        [$cart, $headers] = ($this->cart)();

        return array_column($this->postJson("/api/storefront/v1/checkout/{$cart}/quote", [], $headers)->json('data.payment_methods'), 'code');
    };
    $this->status = fn (string $id) => DB::table('plugins')->where('id', $id)->value('status');
    $this->refreshActivation = function () {
        PluginActivation::forgetCache();
        app(PluginActivation::class)->flush();
    };
});

it('draining: cổng không còn ở checkout, đơn mới qua cổng bị từ chối; khoản cũ vẫn ghi nhận; tự tắt khi hết việc', function () {
    ($this->place)('cod', 'drain-order-key-0001')->assertCreated();
    expect(($this->methods)())->toContain('cod');

    $this->artisan('vani:plugin:disable', ['plugin' => 'vani.cod', '--drain' => true])
        ->expectsOutputToContain('đang ngừng (draining)')->expectsOutputToContain('còn 1 khoản thanh toán chờ')->assertSuccessful();
    ($this->refreshActivation)();
    expect(($this->status)('vani.cod'))->toBe('draining')
        ->and(($this->methods)())->not->toContain('cod');
    ($this->place)('cod', 'drain-order-key-0002')->assertStatus(422);

    // Giao dịch đang dở vẫn chạy: cổng COD vẫn có trong registry (thu COD khi giao).
    expect(app(GatewayRegistry::class)->get('cod'))->not->toBeNull();

    $this->artisan('vani:plugin:finish-draining')->expectsOutputToContain('Không có plugin nào')->assertSuccessful();
    expect(($this->status)('vani.cod'))->toBe('draining');

    Payment::query()->update(['status' => 'paid']);
    $this->artisan('vani:plugin:finish-draining')->expectsOutputToContain('Đã tắt: vani.cod')->assertSuccessful();
    expect(($this->status)('vani.cod'))->toBe('disabled')
        ->and(DB::table('audit_logs')->where('subject_id', 'vani.cod')->orderBy('id')->pluck('action')->all())->toBe(['extension.plugin.draining', 'extension.plugin.drained']);
});

it('không có việc dở dang → --drain tắt ngay; bật lại huỷ draining', function () {
    $this->artisan('vani:plugin:disable', ['plugin' => 'vani.bank-transfer', '--drain' => true])->expectsOutputToContain('đã tắt')->assertSuccessful();
    expect(($this->status)('vani.bank-transfer'))->toBe('disabled');

    ($this->place)('cod', 'drain-order-key-0003')->assertCreated();
    $this->artisan('vani:plugin:disable', ['plugin' => 'vani.cod', '--drain' => true])->assertFailed(); // cổng cuối: không cho ngừng
    $this->artisan('vani:plugin:enable', ['plugin' => 'vani.bank-transfer'])->assertSuccessful();
    $this->artisan('vani:plugin:disable', ['plugin' => 'vani.cod', '--drain' => true])->assertSuccessful();
    expect(($this->status)('vani.cod'))->toBe('draining');

    $this->artisan('vani:plugin:enable', ['plugin' => 'vani.cod'])->assertSuccessful();
    ($this->refreshActivation)();
    expect(($this->status)('vani.cod'))->toBe('enabled')->and(($this->methods)())->toContain('cod');
});

it('hãng đang ngừng: vận đơn đang giao vẫn dùng hãng đó, vận đơn mới về hãng mặc định', function () {
    $extensions = app(Extensions::class);
    $extensions->contribute(ShippingCarrier::CARRIERS_TAG, FakeApiCarrier::class, 'fixture.carrier');
    DB::table('plugins')->insert(['id' => 'fixture.carrier', 'version' => '1.0.0', 'status' => 'draining', 'installed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    ($this->refreshActivation)();

    $registry = app(CarrierRegistry::class);
    expect($registry->carrier('fake_api'))->not->toBeNull()          // webhook/tra cứu vận đơn cũ
        ->and($registry->acceptsNewShipments('fake_api'))->toBeFalse()
        ->and($registry->acceptsNewShipments('manual'))->toBeTrue();
});
