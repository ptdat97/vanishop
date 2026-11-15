<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Fulfillment\Tests\Feature\Fixtures\FakeApiCarrier;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;
use Plugin\CarrierRouting\CarrierRoutingServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| vani.carrier-routing — tham chiếu EP v3 X3 (CarrierAssignment): chọn hãng cho vận đơn mới theo tỉnh/thu hộ; lý do ghi vào
| lịch sử vận đơn; hãng chưa bật → quy tắc mặc định.
*/

beforeEach(function () {
    C::autoConfirmCod();
    app(Extensions::class)->tag([FakeApiCarrier::class], CarrierRegistry::CARRIERS_TAG);
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(CarrierRoutingServiceProvider::ID);
        app(PluginManager::class)->enable(CarrierRoutingServiceProvider::ID);
    });
    app()->register(CarrierRoutingServiceProvider::class);
    ['s' => $this->s] = C::store(stock: 20);
    $this->rules = fn (string $rules) => T::seed(fn () => app(Settings::class)->set(CarrierRoutingServiceProvider::ID, 'rules', $rules));
    $this->place = function (): Shipment {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'k-'.$created->json('data.id')])->assertCreated();

        return Shipment::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    };
});

it('dòng đầu tiên khớp thắng: tỉnh nhận hàng → hãng + dịch vụ, lý do vào lịch sử vận đơn', function () {
    ($this->rules)("cod>=99000000 = manual\ntinh:01, 29 = fake_api:express\n* = manual");

    $shipment = ($this->place)();

    expect([$shipment->carrier_code, $shipment->service_code])->toBe(['fake_api', 'express'])
        ->and(DB::table('shipment_events')->where('shipment_id', $shipment->id)->value('description'))->toBe('Quy tắc chọn hãng: tinh:01, 29 = fake_api:express');
});

it('theo mức thu hộ; hãng chưa bật hoặc không quy tắc nào khớp → quy tắc mặc định', function () {
    ($this->rules)("cod>=300000 = fake_api\n* = manual");
    expect(($this->place)()->carrier_code)->toBe('fake_api');

    ($this->rules)('cod>=300000 = hang_chua_bat');
    expect(($this->place)()->carrier_code)->toBe(config('vanishop.fulfillment.default_carrier'));

    ($this->rules)("tinh:01 = fake_api\ndòng sai cú pháp");
    expect(($this->place)()->carrier_code)->toBe(config('vanishop.fulfillment.default_carrier'));
});
