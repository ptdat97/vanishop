<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
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

it('xem trước phân loại từng dòng, chưa ghi gì', function () {
    $this->post('/admin/fulfillment/cod-statements/preview', ['file' => ($this->file)()])->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Fulfillment::CodStatements/Preview')
        ->where('preview.lines', fn (mixed $lines) => collect($lines)->pluck('status', 'line')->all() === [2 => 'matched', 3 => 'amount_mismatch', 4 => 'not_delivered', 5 => 'not_found', 6 => 'duplicate', 7 => 'invalid'])
        ->where('preview.lines.0.fee', 22_000)
        ->where('preview.total', 330_000)
        ->where('urls.apply', fn (?string $url) => $url !== null));

    expect(DB::table('reconciliation_entries')->count())->toBe(0)
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
        ->and(DB::table('audit_logs')->where('action', 'payment.cod_statement.imported')->exists())->toBeTrue();

    // Token dùng một lần; tải lại cùng file → mọi dòng đã đối soát, không có gì để ghi.
    $this->post($preview['urls']['apply'])->assertSessionHasErrors('file');
    $this->post('/admin/fulfillment/cod-statements/preview', ['file' => ($this->file)()])->assertInertia(fn (Assert $page) => $page
        ->where('preview.summary.already', 4)->where('urls.apply', null));
    expect(DB::table('reconciliation_entries')->count())->toBe(4);
});

it('file thiếu cột mã vận đơn / tiền thu hộ: báo, không cho áp dụng; không có quyền xác nhận thì bị chặn', function () {
    $file = UploadedFile::fake()->createWithContent('sai.csv', "Đơn,Số tiền khác\nGHN001,330000\n");
    $this->post('/admin/fulfillment/cod-statements/preview', ['file' => $file])->assertInertia(fn (Assert $page) => $page
        ->where('preview.lines.0.status', 'header_missing')->where('urls.apply', null));

    $this->actingAs(T::staff(['admin.access', 'fulfillment.view', 'fulfillment.manage']), 'staff');
    $this->post('/admin/fulfillment/cod-statements/preview', ['file' => ($this->file)()])->assertForbidden();
    $this->get('/admin/fulfillment/shipments')->assertInertia(fn (Assert $page) => $page->where('codStatementUrl', null));
});
