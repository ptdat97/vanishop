<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Contracts\DeliveryStatistics;
use Modules\Payment\Contracts\Payments;
use Modules\Payment\Persistence\Models\Payment;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Stabilization A9: nhập bảng kê COD của hãng — xem trước (chưa ghi), áp dụng: khớp → sổ đối soát "matched" + ghi nhận đã thu
| nếu chưa; lệch tiền / không có vận đơn / chưa giao → hàng đợi "Đối soát lệch"; nhập lại cùng file không ghi lần hai.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store();
    C::autoConfirmCod();
    $api = '/api/storefront/v1';
    $this->placeCod = function (string $tracking, string $status, string $key) use ($api): int {
        $created = $this->postJson("{$api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => 'cod', 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => $key])->assertCreated();
        $shipment = DB::table('shipments')->latest('id')->first();
        DB::table('shipments')->where('id', $shipment->id)->update(['tracking_number' => $tracking, 'status' => $status]);

        return (int) $shipment->order_id;
    };
    $this->delivered = ($this->placeCod)('GHN001', 'delivered', 'cod-key-0001');
    $this->short = ($this->placeCod)('GHN002', 'delivered', 'cod-key-0002');
    $this->transit = ($this->placeCod)('GHN003', 'in_transit', 'cod-key-0003');
    $this->file = fn () => UploadedFile::fake()->createWithContent('bang-ke.csv', implode("\n", [
        'STT;Mã vận đơn;Tiền thu hộ;Phí',
        '1;ghn001;330.000;22.000',
        '2;GHN002;300.000;22.000',
        '3;GHN003;330.000;0',
        '4;KHONGCO;100.000;0',
        '5;GHN001;330.000;22.000',
        '6;GHN009;abc;0',
    ]));
    $this->actingAs(T::staff(['admin.access', 'fulfillment.view', 'payments.confirm']), 'staff');
});

it('tính tuổi COD của kiện đã giao chưa khớp bảng kê và bỏ kiện đã khớp', function () {
    $asOf = new DateTimeImmutable('2026-11-20T00:00:00+00:00');
    DB::table('shipments')->where('order_id', $this->delivered)->update(['delivered_at' => '2026-11-16 00:00:00']);
    DB::table('shipments')->where('order_id', $this->short)->update(['delivered_at' => '2026-11-13 00:00:00']);
    $preview = $this->post('/admin/fulfillment/cod-statements/preview', ['file' => ($this->file)()]);
    $this->post($preview->viewData('page')['props']['urls']['apply'])->assertSessionHas('success');

    expect(app(DeliveryStatistics::class)->codAging($asOf->modify('-30 days'), $asOf, $asOf))
        ->toBe([['carrier' => 'manual', 'bucket' => '7-14', 'shipments' => 1, 'amount' => 330_000]]);
});

it('xem trước phân loại từng dòng, chưa ghi gì', function () {
    $this->post('/admin/fulfillment/cod-statements/preview', ['file' => ($this->file)()])->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Fulfillment::CodStatements/Preview')
        ->where('preview.lines', fn (mixed $lines) => collect($lines)->pluck('status', 'line')->all() === [2 => 'matched', 3 => 'amount_mismatch', 4 => 'not_delivered', 5 => 'not_found', 6 => 'duplicate', 7 => 'invalid'])
        ->where('preview.lines.0.fee', 22_000)
        ->where('preview.total', 330_000)
        ->where('urls.apply', fn (?string $url) => $url !== null));

    expect(DB::table('reconciliation_entries')->count())->toBe(0)
        ->and(DB::table('shipments')->whereNotNull('actual_carrier_fee')->exists())->toBeFalse()
        ->and(Payment::query()->withoutGlobalScopes()->where('order_id', $this->delivered)->value('status')->value)->toBe('pending');
});

it('áp dụng: dòng khớp ghi nhận đã thu và vào sổ là khớp; dòng lệch vào hàng đợi; nhập lại không ghi lần hai', function () {
    $preview = $this->post('/admin/fulfillment/cod-statements/preview', ['file' => ($this->file)()])->viewData('page')['props'];
    $this->post($preview['urls']['apply'])->assertRedirect('/admin/payment/payments')->assertSessionHas('success');

    expect(DB::table('reconciliation_entries')->orderBy('reference')->get(['reference', 'status', 'order_id', 'expected', 'actual'])->map(fn (object $row) => (array) $row)->all())->toEqual([
        ['reference' => 'GHN001', 'status' => 'matched', 'order_id' => $this->delivered, 'expected' => 330_000, 'actual' => 330_000],
        ['reference' => 'GHN002', 'status' => 'mismatch', 'order_id' => $this->short, 'expected' => 330_000, 'actual' => 300_000],
        ['reference' => 'GHN003', 'status' => 'mismatch', 'order_id' => $this->transit, 'expected' => 330_000, 'actual' => 330_000],
        ['reference' => 'KHONGCO', 'status' => 'mismatch', 'order_id' => null, 'expected' => null, 'actual' => 100_000],
    ])
        ->and(Payment::query()->withoutGlobalScopes()->where('order_id', $this->delivered)->value('status')->value)->toBe('paid')
        ->and(Payment::query()->withoutGlobalScopes()->where('order_id', $this->short)->value('status')->value)->toBe('pending')
        ->and(DB::table('shipments')->whereNotNull('actual_carrier_fee')->pluck('actual_carrier_fee', 'tracking_number')->all())->toBe(['GHN001' => 22_000])
        ->and(DB::table('audit_logs')->where('action', 'payment.cod_statement.imported')->exists())->toBeTrue();

    // Token dùng một lần; tải lại cùng file → mọi dòng đã đối soát, không có gì để ghi.
    $this->post($preview['urls']['apply'])->assertSessionHasErrors('file');
    $this->post('/admin/fulfillment/cod-statements/preview', ['file' => ($this->file)()])->assertInertia(fn (Assert $page) => $page
        ->where('preview.summary.already', 4)->where('urls.apply', null));
    expect(DB::table('reconciliation_entries')->count())->toBe(4);
});

it('bổ sung phí vào kiện đã khớp, giữ phí 0 và báo khi phí gửi lại khác số đã lưu', function () {
    $preview = function (?int $fee = null): array {
        $suffix = $fee === null ? '' : (string) $fee;
        $file = UploadedFile::fake()->createWithContent('bang-ke.csv', "Mã vận đơn;Tiền thu hộ;Phí\nGHN001;330000;{$suffix}");

        return $this->post('/admin/fulfillment/cod-statements/preview', ['file' => $file])->assertOk()->viewData('page')['props'];
    };
    $this->post($preview()['urls']['apply'])->assertSessionHas('success');
    $withFee = $preview(0);
    expect($withFee['preview']['summary'])->toBe(['fee_only' => 1]);
    $this->post($withFee['urls']['apply'])->assertSessionHas('success');

    expect(DB::table('shipments')->where('tracking_number', 'GHN001')->value('actual_carrier_fee'))->toBe(0)
        ->and(DB::table('payment_transactions')->where('type', 'cod_collected')->count())->toBe(1)
        ->and($preview(0)['urls']['apply'])->toBeNull()
        ->and($preview(120)['preview']['summary'])->toBe(['fee_conflict' => 1]);
});

it('không đoán hãng khi có hai kiện còn hiệu lực dùng cùng mã vận đơn', function () {
    DB::table('shipments')->where('order_id', $this->transit)->update(['tracking_number' => 'GHN001', 'carrier_code' => 'other']);

    $this->post('/admin/fulfillment/cod-statements/preview', ['file' => UploadedFile::fake()->createWithContent(
        'bang-ke.csv', "Mã vận đơn;Tiền thu hộ;Phí\nGHN001;330000;22000",
    )])->assertInertia(fn (Assert $page) => $page->where('preview.summary.ambiguous', 1)->where('urls.apply', null));
});

it('nhập lại vận đơn đã giao xử lý dòng lệch cũ và chỉ ghi thu COD một lần cho cùng kiện', function (bool $carrierReported) {
    $file = fn () => UploadedFile::fake()->createWithContent('bang-ke.csv', "Mã vận đơn;Tiền thu hộ\nGHN003;100000");
    $preview = fn () => $this->post('/admin/fulfillment/cod-statements/preview', ['file' => $file()])->assertOk()->viewData('page')['props'];
    $shipment = DB::table('shipments')->where('order_id', $this->transit)->first();
    DB::table('shipments')->where('id', $shipment->id)->update(['cod_amount' => 100_000]);
    $first = $preview();
    expect($first['preview']['summary'])->toBe(['not_delivered' => 1]);
    $this->post($first['urls']['apply'])->assertSessionHas('success');
    $oldEntry = DB::table('reconciliation_entries')->sole();

    DB::table('shipments')->where('id', $shipment->id)->update(['status' => 'delivered']);
    if ($carrierReported) {
        app(Payments::class)->collectCod($this->transit, 100_000, "shipment:{$shipment->id}", 100_000);
    }
    $retry = $preview();
    expect($retry['preview']['summary'])->toBe(['matched' => 1]);
    $this->post($retry['urls']['apply'])->assertSessionHas('success');
    app(Payments::class)->collectCod($this->transit, 100_000, "shipment:{$shipment->id}", 100_000);

    $entry = DB::table('reconciliation_entries')->sole();
    $transaction = DB::table('payment_transactions')->where('type', 'cod_collected')->sole();
    expect($entry->id)->toBe($oldEntry->id)
        ->and($entry->status)->toBe('matched')
        ->and((int) $transaction->amount)->toBe(100_000)
        ->and(Payment::query()->withoutGlobalScopes()->where('order_id', $this->transit)->value('status')->value)->toBe('pending')
        ->and($preview()['urls']['apply'])->toBeNull();
})->with([false, true]);

it('có thể thử lại cùng bản xem trước sau lỗi giữa chừng mà không ghi thu lặp', function () {
    DB::table('shipments')->where('order_id', $this->transit)->update(['status' => 'delivered']);
    $file = UploadedFile::fake()->createWithContent('bang-ke.csv', "Mã vận đơn;Tiền thu hộ;Phí\nGHN001;330000;22000\nGHN003;330000;18000");
    $preview = $this->post('/admin/fulfillment/cod-statements/preview', ['file' => $file])->assertOk()->viewData('page')['props'];
    $apply = $preview['urls']['apply'];
    $payments = app(Payments::class);
    $mock = Mockery::mock(Payments::class);
    $mock->shouldReceive('collectCod')->once()->with($this->delivered, 330_000, Mockery::type('string'), 330_000)
        ->andReturnUsing($payments->collectCod(...));
    $mock->shouldReceive('collectCod')->once()->with($this->transit, 330_000, Mockery::type('string'), 330_000)
        ->andThrow(new RuntimeException('Lỗi tạm thời khi ghi kiện thứ hai'));
    app()->instance(Payments::class, $mock);

    $this->withoutExceptionHandling();
    expect(fn () => $this->post($apply))->toThrow(RuntimeException::class, 'Lỗi tạm thời khi ghi kiện thứ hai');
    expect(DB::table('reconciliation_entries')->pluck('reference')->all())->toBe(['GHN001']);
    expect(DB::table('shipments')->whereNotNull('actual_carrier_fee')->pluck('actual_carrier_fee', 'tracking_number')->all())->toBe(['GHN001' => 22_000]);

    app()->instance(Payments::class, $payments);
    $this->post($apply)->assertSessionHas('success');
    expect(DB::table('reconciliation_entries')->orderBy('reference')->pluck('reference')->all())->toBe(['GHN001', 'GHN003'])
        ->and(DB::table('payment_transactions')->where('type', 'cod_collected')->count())->toBe(2)
        ->and(DB::table('shipments')->whereNotNull('actual_carrier_fee')->orderBy('tracking_number')->pluck('actual_carrier_fee', 'tracking_number')->all())->toBe(['GHN001' => 22_000, 'GHN003' => 18_000]);
    $this->withExceptionHandling()->post($apply)->assertSessionHasErrors('file');
});

it('file thiếu cột mã vận đơn / tiền thu hộ: báo, không cho áp dụng; không có quyền xác nhận thì bị chặn', function () {
    $file = UploadedFile::fake()->createWithContent('sai.csv', "Đơn,Số tiền khác\nGHN001,330000\n");
    $this->post('/admin/fulfillment/cod-statements/preview', ['file' => $file])->assertInertia(fn (Assert $page) => $page
        ->where('preview.lines.0.status', 'header_missing')->where('urls.apply', null));

    $this->actingAs(T::staff(['admin.access', 'fulfillment.view', 'fulfillment.manage']), 'staff');
    $this->post('/admin/fulfillment/cod-statements/preview', ['file' => ($this->file)()])->assertForbidden();
    $this->get('/admin/fulfillment/shipments')->assertInertia(fn (Assert $page) => $page->where('codStatementUrl', null));
});
