<?php

use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Fulfillment\Tests\Feature\Fixtures\FakeApiCarrier;
use Modules\Payment\Persistence\Models\Payment;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Tắt plugin khi implementation còn việc dở dang (ADR-029 bổ sung, 0.3.16): cổng còn khoản chờ/giữ tiền, hãng còn vận đơn
| chưa kết thúc → từ chối (IPN/webhook sẽ 404, tiền/hàng không được ghi nhận); --force cho khẩn cấp, có audit.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
    $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['payment_method' => 'cod', 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'guard-0001'])->assertCreated();
});

it('cổng còn khoản thanh toán chờ: không tắt được; --force tắt được và ghi audit', function () {
    $this->artisan('vani:plugin:disable', ['plugin' => 'vani.cod'])
        ->expectsOutputToContain('còn 1 khoản thanh toán chờ/giữ tiền qua cổng cod')
        ->assertFailed();
    expect(DB::table('plugins')->where('id', 'vani.cod')->value('status'))->toBe('enabled');

    // --force phải xác nhận tường minh: từ chối → huỷ; không tương tác mà thiếu --yes → huỷ.
    $this->artisan('vani:plugin:disable', ['plugin' => 'vani.cod', '--force' => true])
        ->expectsOutputToContain('còn 1 khoản thanh toán chờ/giữ tiền qua cổng cod')
        ->expectsConfirmation('Xác nhận tắt ngay (sẽ ghi audit)?', 'no')->assertFailed();
    $this->artisan('vani:plugin:disable', ['plugin' => 'vani.cod', '--force' => true, '--no-interaction' => true])->assertFailed();
    expect(DB::table('plugins')->where('id', 'vani.cod')->value('status'))->toBe('enabled');

    $this->artisan('vani:plugin:disable', ['plugin' => 'vani.cod', '--force' => true])
        ->expectsConfirmation('Xác nhận tắt ngay (sẽ ghi audit)?', 'yes')->assertSuccessful();
    $audit = DB::table('audit_logs')->where('action', 'extension.plugin.disabled')->where('subject_id', 'vani.cod')->sole();
    expect(DB::table('plugins')->where('id', 'vani.cod')->value('status'))->toBe('disabled')
        ->and(json_decode((string) $audit->changes, true))->toMatchArray(['forced' => true]);
});

it('khoản thanh toán đã kết thúc thì tắt bình thường', function () {
    Payment::query()->update(['status' => 'paid']);

    $this->artisan('vani:plugin:disable', ['plugin' => 'vani.cod'])->assertSuccessful();
});

it('hãng còn vận đơn chưa kết thúc: plugin hãng bị chặn tắt cho tới khi vận đơn xong', function () {
    $extensions = app(Extensions::class);
    $extensions->contribute(ShippingCarrier::CARRIERS_TAG, FakeApiCarrier::class, 'fixture.carrier');
    $shipment = Shipment::query()->sole();
    $shipment->update(['carrier_code' => 'fake_api']);

    expect($extensions->disableBlockers('fixture.carrier'))->toBe(['còn 1 vận đơn chưa kết thúc của hãng fake_api'])
        ->and($extensions->disableBlockers('vani.bank-transfer'))->toBe([]);

    $shipment->update(['status' => ShipmentStatus::Delivered]);
    expect($extensions->disableBlockers('fixture.carrier'))->toBe([]);
});
