<?php

use Illuminate\Support\ViewErrorBag;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\Phones;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

/*
| Roadmap Phase 7: khách gửi yêu cầu đổi/trả trên storefront native (không cần JS), cả khi xem đơn bằng tài khoản và
| bằng token đơn trong phiên (vừa đặt). Đổi size/màu cùng mẫu chọn trong form; size hết hàng bị khoá.
*/

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s, 'm' => $this->m, 'location' => $this->location] = C::store(); // "Đầm lụa" S + M
    $this->placeDelivered = function (string $key): Order {
        $this->post('/gio-hang', ['variant_id' => $this->s->id, 'quantity' => 2]);
        $this->post('/thanh-toan', [
            'contact' => ['full_name' => 'Lan', 'phone' => '0912345678'],
            'shipping_address' => ['province_code' => '01', 'ward_code' => '10105001', 'street_line' => '1 Tràng Tiền'],
            'shipping_method' => 'standard', 'payment_method' => 'cod', 'expected_total' => 600_000, 'idempotency_key' => $key,
        ])->assertRedirect();
        $order = Order::query()->latest('id')->first();
        app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($order) {
            $shipment = Shipment::query()->where('order_id', $order->id)->sole();
            app(FulfillmentService::class)->book($shipment->id, 'T'.$shipment->id);
            foreach ([ShipmentStatus::PickedUp, ShipmentStatus::Delivered] as $status) {
                app(FulfillmentService::class)->updateStatus($shipment->id, $status, "test:{$shipment->id}:{$status->value}", 'staff');
            }
        });

        return $order;
    };
    $this->signIn = function (): void {
        $this->post('/tai-khoan/dang-nhap/otp', ['phone' => '0912345678']);
        $this->post('/tai-khoan/dang-nhap', ['code' => FakeOtpSender::$codes[Phones::fromString('0912345678')->e164.'|login']]);
    };
});

it('khách đăng nhập: form đổi/trả trên trang đơn; đổi size cùng mẫu (size hết hàng bị khoá); huỷ yêu cầu chờ duyệt', function () {
    ($this->signIn)();
    $order = ($this->placeDelivered)('native-return-0001');
    $lineId = $order->lines()->value('id');
    $page = "/tai-khoan/don-hang/{$order->public_id}";

    $this->get($page)->assertOk()
        ->assertSee('Gửi yêu cầu đổi/trả')
        ->assertSee('name="lines['.$lineId.'][exchange_variant_id]"', false)
        ->assertSee('value="'.$this->m->id.'"', false)
        ->assertSee('(như cũ)');

    I::stock($this->location, $this->m->id, 0);
    $this->get($page)->assertSee('— hết hàng');
    I::stock($this->location, $this->m->id, 5);

    $this->from($page)->post("/don-hang/{$order->public_id}/doi-tra", [
        'resolution' => 'exchange', 'lines' => [$lineId => ['quantity' => 1]], 'reason_code' => 'wrong_size',
    ])->assertRedirect($page)->assertSessionHasErrors('lines');

    $this->from($page)->post("/don-hang/{$order->public_id}/doi-tra", [
        'resolution' => 'exchange', 'lines' => [$lineId => ['quantity' => 1, 'exchange_variant_id' => $this->m->id]], 'reason_code' => 'wrong_size', 'note' => 'Chật',
    ])->assertRedirect($page)->assertSessionHas('status');

    $return = ReturnRequest::query()->sole();
    expect($return->resolution)->toBe('exchange')->and($return->source)->toBe('customer')
        ->and($return->lines()->value('exchange_variant_id'))->toBe($this->m->id);
    $this->get($page)->assertSee($return->number)->assertSee('Đổi hàng')->assertSee('Chờ duyệt')->assertSee('Huỷ yêu cầu');

    $this->from($page)->post("/don-hang/{$order->public_id}/doi-tra/{$return->public_id}/huy")->assertRedirect($page);
    expect($return->fresh()->status->value)->toBe('cancelled');
});

it('xem đơn bằng token trong phiên (vừa đặt, không đăng nhập): trả hàng hoàn tiền; vượt số lượng → báo lỗi', function () {
    $order = ($this->placeDelivered)('native-return-0002');
    $lineId = $order->lines()->value('id');
    $page = "/don-hang/{$order->public_id}";

    $this->get($page)->assertOk()->assertSee('Gửi yêu cầu đổi/trả');
    $this->from($page)->post("{$page}/doi-tra", ['resolution' => 'refund', 'lines' => [$lineId => ['quantity' => 5]], 'reason_code' => 'changed_mind'])
        ->assertRedirect($page)->assertSessionHasErrors('business');
    $this->from($page)->post("{$page}/doi-tra", ['resolution' => 'refund', 'lines' => [$lineId => ['quantity' => 0]], 'reason_code' => 'changed_mind'])
        ->assertSessionHasErrors('lines');
    $this->from($page)->post("{$page}/doi-tra", ['resolution' => 'refund', 'lines' => [$lineId => ['quantity' => 2]], 'reason_code' => 'changed_mind'])
        ->assertRedirect($page)->assertSessionHas('status');

    expect(ReturnRequest::query()->sole())->resolution->toBe('refund')->refund_amount->toBe(600_000);
    // Đã trả hết → không còn form, chỉ còn danh sách yêu cầu.
    $this->get($page)->assertDontSee('Gửi yêu cầu đổi/trả')->assertSee('Tiền hoàn dự kiến');
});

it('người khác (không token, không phải chủ đơn) không gửi được yêu cầu', function () {
    $order = ($this->placeDelivered)('native-return-0003');
    $lineId = $order->lines()->value('id');
    $this->flushSession();

    $this->post("/don-hang/{$order->public_id}/doi-tra", ['resolution' => 'refund', 'lines' => [$lineId => ['quantity' => 1]], 'reason_code' => 'other'])->assertNotFound();
    expect(ReturnRequest::query()->count())->toBe(0);
});

it('đơn chưa giao: không có form đổi/trả', function () {
    $this->post('/gio-hang', ['variant_id' => $this->s->id]);
    $this->post('/thanh-toan', [
        'contact' => ['full_name' => 'Lan', 'phone' => '0912345678'],
        'shipping_address' => ['province_code' => '01', 'ward_code' => '10105001', 'street_line' => '1 Tràng Tiền'],
        'shipping_method' => 'standard', 'payment_method' => 'cod', 'expected_total' => 330_000, 'idempotency_key' => 'native-return-0004',
    ]);
    $this->get('/don-hang/'.Order::query()->sole()->public_id)->assertOk()->assertDontSee('Gửi yêu cầu đổi/trả');
});

/*
| Test song kênh (ADR-044): cùng yêu cầu đổi/trả gửi qua form Blade và qua Storefront API cho cùng kết quả. Form Blade gửi
| `lines[<id dòng>][...]` có cả dòng số lượng 0; API gửi danh sách — ReturnRequestForm đưa về một dạng, một luật.
*/
dataset('return scenarios', [
    'trả 1 sản phẩm, hoàn tiền' => ['refund', 1, false, ['created' => true, 'resolution' => 'refund', 'quantity' => 1, 'error_fields' => []]],
    'không chọn dòng nào' => ['refund', 0, false, ['created' => false, 'resolution' => null, 'quantity' => null, 'error_fields' => ['lines']]],
    'đổi hàng nhưng thiếu hàng đổi' => ['exchange', 1, false, ['created' => false, 'resolution' => null, 'quantity' => null, 'error_fields' => ['lines']]],
    'đổi sang size khác' => ['exchange', 1, true, ['created' => true, 'resolution' => 'exchange', 'quantity' => 1, 'error_fields' => []]],
    'trả quá số đã mua' => ['refund', 5, false, ['created' => false, 'resolution' => null, 'quantity' => null, 'error_fields' => ['business']]],
]);

it('đổi/trả qua Blade và qua API cho cùng kết quả', function (string $resolution, int $quantity, bool $withVariant, array $expected) {
    $outcome = function (bool $accepted, array $errorFields): array {
        $return = $accepted ? ReturnRequest::query()->with('lines')->latest('id')->first() : null;
        sort($errorFields);

        return [
            'created' => $return !== null,
            'resolution' => $return?->resolution,
            'quantity' => $return?->lines->sum('quantity'),
            'error_fields' => array_values(array_unique($errorFields)),
        ];
    };

    // Blade: form trên trang đơn (token đơn trong phiên).
    $order = ($this->placeDelivered)('parity-return-blade');
    $lineId = $order->lines()->value('id');
    $page = "/don-hang/{$order->public_id}";
    $line = ['quantity' => $quantity, ...($withVariant ? ['exchange_variant_id' => $this->m->id] : [])];
    $before = ReturnRequest::query()->count();
    $this->from($page)->post("{$page}/doi-tra", ['resolution' => $resolution, 'lines' => [$lineId => $line], 'reason_code' => 'wrong_size']);
    $bag = session('errors');
    $bladeErrors = $bag instanceof ViewErrorBag ? $bag->getBag('default')->keys() : array_keys((array) ($bag['default']['messages'] ?? []));
    $blade = $outcome(ReturnRequest::query()->count() > $before, $bladeErrors);

    // API: danh sách dòng, token đơn trong header.
    $order = ($this->placeDelivered)('parity-return-api');
    $lineId = $order->lines()->value('id');
    $token = (string) session("vani.orders.{$order->public_id}.token");
    $lines = $quantity === 0 ? [] : [['order_line_id' => $lineId, 'quantity' => $quantity, ...($withVariant ? ['exchange_variant_id' => $this->m->id] : [])]];
    $response = $this->postJson("/api/storefront/v1/orders/{$order->public_id}/returns", ['resolution' => $resolution, 'lines' => $lines, 'reason_code' => 'wrong_size'], ['X-Vani-Order-Token' => $token]);
    $apiErrors = match (true) {
        $response->status() === 201 => [],
        $response->json('error.code') === 'validation.failed' => array_map(fn (string $field): string => explode('.', $field)[0], array_column((array) $response->json('error.details'), 'field')),
        default => ['business'],
    };
    $api = $outcome($response->status() === 201, $apiErrors);

    expect($blade)->toBe($api)->and($api)->toBe($expected);
})->with('return scenarios');
