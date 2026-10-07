<?php

use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Contracts\Data\GatewayStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;
use Modules\Shared\Domain\Money\Money;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    app(Extensions::class)->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    FakeOnlineGateway::$queryResult = null;
    FakeOnlineGateway::$queryException = null;
    ['s' => $s] = C::store();
    $place = function (string $method, string $key) use ($s) {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $s->id, 'quantity' => 1], $headers)->assertOk();

        return $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['payment_method' => $method, 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => $key])->assertCreated()->json('data.payment');
    };
    $online = $place('fake_online', 'verify-online-1');
    $this->postJson('/api/payments/fake_online/callback', FakeOnlineGateway::callbackPayload($online['id'], 'TXN-VERIFY-1', 'paid', 330_000))->assertOk();
    $this->online = Payment::query()->where('public_id', $online['id'])->sole();
    $place('cod', 'verify-cod-1');
    Payment::query()->where('gateway_code', 'cod')->update(['status' => 'paid']); // COD đã thu: không có giao dịch phía cổng để tra
    $this->lines = fn () => DB::table('payment_reconciliation_lines')->orderBy('id')->get(['issue', 'expected', 'actual'])->map(fn ($line) => (array) $line)->all();
});

afterEach(function () {
    FakeOnlineGateway::$queryResult = null;
    FakeOnlineGateway::$queryException = null; // static: không để lọt sang file test khác cùng tiến trình
});

it('khớp với cổng → không chênh lệch; COD/chuyển khoản tay bị bỏ qua', function () {
    FakeOnlineGateway::$queryResult = new GatewayStatus(GatewayCallback::PAID, 'TXN-VERIFY-1', Money::vnd(330_000));

    $this->artisan('vani:payment:verify')->expectsOutputToContain('Đã đối chiếu 1 khoản')->assertSuccessful();
    expect(($this->lines)())->toBe([]);
});

it('cổng báo chưa thu, sai số tiền, hoàn khác → ghi đúng loại; không đổi trạng thái thanh toán; chạy lại không ghi trùng', function () {
    FakeOnlineGateway::$queryResult = new GatewayStatus(GatewayCallback::PENDING, null, Money::vnd(300_000), refunded: Money::vnd(30_000));

    $this->artisan('vani:payment:verify')->assertFailed();
    expect(($this->lines)())->toBe([
        ['issue' => 'gateway_not_captured', 'expected' => 'paid', 'actual' => 'pending'],
        ['issue' => 'amount_mismatch', 'expected' => '330000', 'actual' => '300000'],
        ['issue' => 'refund_mismatch', 'expected' => '0', 'actual' => '30000'],
    ])->and($this->online->fresh()->status->value)->toBe('paid')
        ->and($this->online->fresh()->refunded_amount)->toBe(0);

    $this->artisan('vani:payment:verify')->assertSuccessful(); // lỗi cũ còn mở: không ghi lại
    expect(DB::table('payment_reconciliation_lines')->count())->toBe(3)
        ->and((int) DB::table('payment_reconciliations')->latest('id')->value('issues'))->toBe(0);
});

it('lỗi gọi cổng → bỏ qua khoản đó (đếm skipped), không dừng cả lượt', function () {
    FakeOnlineGateway::$queryException = new RuntimeException('cổng timeout');

    $this->artisan('vani:payment:verify')->expectsOutputToContain('bỏ qua 1')->assertSuccessful();
});
