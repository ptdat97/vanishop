<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Returns\Application\ReturnService;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Returns\Persistence\Models\ReturnReason;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $this->api = '/api/storefront/v1';
    $this->reasons = '/admin/returns/reasons';

    // Đơn COD 2 × S đã giao.
    $created = $this->postJson("{$this->api}/carts")->assertCreated();
    $cartHeaders = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $cart = $created->json('data.id');
    $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $cartHeaders)->assertOk();
    $placed = $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 600_000]), [...$cartHeaders, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        $shipment = Shipment::query()->latest('id')->first();
        $service = app(FulfillmentService::class);
        $service->book($shipment->id, 'T'.$shipment->id);
        foreach ([ShipmentStatus::PickedUp, ShipmentStatus::Delivered] as $status) {
            $service->updateStatus($shipment->id, $status, "test:{$status->value}", 'staff');
        }
    });
    $this->order = Order::query()->withoutGlobalScopes()->latest('id')->first();
    $this->headers = ['X-Vani-Order-Token' => $placed->json('data.access_token')];
    $this->request = fn (string $reason) => $this->postJson("{$this->api}/orders/{$this->order->public_id}/returns",
        ['lines' => [['order_line_id' => $this->order->lines()->value('id'), 'quantity' => 1]], 'reason_code' => $reason], $this->headers);
});

it('danh mục ban đầu lấy từ danh sách cũ, có tên theo ngôn ngữ và cờ lỗi do cửa hàng', function () {
    expect(ReturnReason::query()->orderBy('position')->pluck('code')->all())->toBe(['wrong_size', 'not_as_described', 'defective', 'changed_mind', 'other'])
        ->and(ReturnReason::query()->where('code', 'defective')->first())->store_fault->toBeTrue()
        ->and(ReturnReason::query()->where('code', 'wrong_size')->first()->names)->toMatchArray(['vi' => 'Sai size', 'en' => 'Wrong size']);
});

it('Admin thêm/sửa/tắt lý do; lý do đã dùng không đổi mã, không xoá; khách chỉ chọn được lý do đang bật', function () {
    $this->actingAs(T::staff(['admin.access', 'returns.view', 'returns.manage']), 'staff');

    $this->post($this->reasons, ['code' => 'wrong_color', 'names' => ['vi' => '', 'en' => 'Wrong colour']])->assertSessionHasErrors('names.vi');
    $this->post($this->reasons, ['code' => 'wrong_color', 'names' => ['vi' => 'Sai màu', 'en' => 'Wrong colour'], 'position' => 9, 'is_active' => true, 'store_fault' => true])->assertSessionHasNoErrors();
    ($this->request)('wrong_color')->assertCreated()->assertJsonPath('data.returns.0.reason', 'Sai màu');

    $reason = ReturnReason::query()->where('code', 'wrong_color')->first();
    $this->put("{$this->reasons}/{$reason->id}", ['code' => 'mau_khac', 'names' => ['vi' => 'Sai màu']])->assertSessionHasErrors('code');
    $this->delete("{$this->reasons}/{$reason->id}")->assertSessionHasErrors('business');
    $this->put("{$this->reasons}/{$reason->id}", ['code' => 'wrong_color', 'names' => ['vi' => 'Sai màu sắc'], 'is_active' => false])->assertSessionHasNoErrors();

    ($this->request)('wrong_color')->assertUnprocessable()->assertJsonPath('error.code', 'validation.failed');
    // Yêu cầu cũ vẫn hiện tên (lý do đã tắt), theo ngôn ngữ của request.
    $this->getJson("{$this->api}/orders/{$this->order->public_id}", $this->headers)->assertJsonPath('data.returns.0.reason', 'Sai màu sắc');

    $unused = ReturnReason::query()->where('code', 'other')->first();
    $this->delete("{$this->reasons}/{$unused->id}")->assertSessionHasNoErrors();
    expect(ReturnReason::query()->where('code', 'other')->exists())->toBeFalse()
        ->and(AuditLog::query()->where('action', 'return.reason_deleted')->exists())->toBeTrue();
});

it('chỉ nhân viên có returns.manage sửa được danh mục', function () {
    $this->actingAs(T::staff(['admin.access', 'returns.view']), 'staff');

    $this->get($this->reasons)->assertOk();
    $this->post($this->reasons, ['code' => 'x', 'names' => ['vi' => 'X']])->assertForbidden();
});

it('khách báo đã gửi hàng về (API, web): chỉ khi đã duyệt; lưu mã vận đơn, chuyển "đang gửi về"', function () {
    ($this->request)('wrong_size')->assertCreated();
    $return = ReturnRequest::query()->latest('id')->first();
    $shipped = fn (array $body = []) => $this->postJson("{$this->api}/orders/{$this->order->public_id}/returns/{$return->public_id}/shipped", $body, $this->headers);

    $shipped()->assertStatus(409)->assertJsonPath('error.code', 'return.transition_invalid');

    $this->actingAs(T::staff(['admin.access', 'returns.view', 'returns.manage']), 'staff')
        ->post("/admin/returns/returns/{$return->id}/transition", ['to' => 'approved', 'lock_version' => $return->lock_version])->assertSessionHasNoErrors();

    $shipped(['tracking_number' => ' GHN123 '])->assertOk()
        ->assertJsonPath('data.returns.0.status', 'in_transit')
        ->assertJsonPath('data.returns.0.return_tracking_number', 'GHN123');
    expect($return->fresh())->return_tracking_number->toBe('GHN123')
        ->and(DB::table('return_events')->where('return_request_id', $return->id)->latest('id')->value('source'))->toBe('customer');
    $shipped()->assertStatus(409);
});

it('trang đơn native: form "Tôi đã gửi hàng về" khi đã duyệt, gửi không cần JS', function () {
    ($this->request)('wrong_size')->assertCreated();
    $return = ReturnRequest::query()->latest('id')->first();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(ReturnService::class)->transition($return->id, ReturnStatus::Approved, null, 'staff'));
    $this->withSession(["vani.orders.{$this->order->public_id}" => ['token' => $this->headers['X-Vani-Order-Token']]]);

    $this->get("/don-hang/{$this->order->public_id}")->assertOk()->assertSee('data-return-shipped', false)->assertSee('Lý do: Sai size');
    $this->post("/don-hang/{$this->order->public_id}/doi-tra/{$return->public_id}/da-gui", ['tracking_number' => 'VT999'])->assertRedirect();
    expect($return->fresh()->status->value)->toBe('in_transit');
    $this->get("/don-hang/{$this->order->public_id}")->assertSee('VT999')->assertDontSee('data-return-shipped', false);
});
