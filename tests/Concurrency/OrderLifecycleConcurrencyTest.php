<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Inventory\Application\StockAdjustmentService;
use Modules\Inventory\Application\StockVerifier;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Ordering\Application\OrderCommands;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;
use Modules\Returns\Application\ReturnService;
use Modules\Returns\Contracts\Returns;
use Modules\Returns\Domain\ReturnStatus;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\BusinessRuleViolation;
use Modules\Shared\Domain\Money\Money;
use Tests\Support\KeepsReferenceData;

/*
| Thao tác vòng đời đơn bị gửi trùng/song song (nhân viên bấm hai lần, hai tab, hãng vận chuyển gửi webhook trùng —
| stabilization C2): mỗi thay đổi xảy ra đúng một lần — tồn kho, sự kiện đơn, hoàn tiền không bị nhân đôi.
*/

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

uses(DatabaseTruncation::class, KeepsReferenceData::class)->group('concurrency');

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Concurrency test cần MySQL (SQLite in-memory không chia sẻ giữa tiến trình).');
    }
    app(Extensions::class)->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    ['s' => $this->variant] = C::store();
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));
});

/**
 * Đặt một đơn 1 × S (300.000 + phí giao 30.000); trả id đơn.
 */
function lifecycleOrder(int $variantId, string $paymentMethod, string $key): int
{
    $cart = app(Carts::class)->create('VND')->key;
    app(Carts::class)->addLine($cart, $variantId, 1);
    $payload = C::orderPayload();
    app(Checkout::class)->placeOrder(new CheckoutRequest($cart, $payload['contact'], $payload['shipping_address'], 'standard', $paymentMethod, [], null, 330_000), $key);

    return (int) DB::table('orders')->latest('id')->value('id');
}

/**
 * Chạy `$count` lần cùng một thao tác ở các tiến trình khác nhau; trả 'ok' hoặc mã lỗi nghiệp vụ.
 *
 * @return list<Closure>
 */
function lifecycleTasks(int $count, string $operation, int $id, int $extra = 0): array
{
    $tasks = [];
    for ($i = 0; $i < $count; $i++) {
        $tasks[] = static function () use ($operation, $id, $extra, $i): string {
            try {
                app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => match ($operation) {
                    'cancel' => app(OrderCommands::class)->cancel($id, 'Khách gọi huỷ', 'staff'),
                    'delivered' => app(FulfillmentService::class)->updateStatus($id, ShipmentStatus::Delivered, "carrier-event-{$i}", 'carrier'),
                    'receive' => app(ReturnService::class)->receive($id, [$extra => 'sellable'], null),
                    'resolve' => app(ReturnService::class)->resolve($id, null, null),
                });

                return 'ok';
            } catch (BusinessRuleViolation $exception) {
                return $exception->errorCode();
            }
        };
    }

    return $tasks;
}

/**
 * Khách đặt hàng từ các giỏ đã tạo + một nhân viên kiểm kê đặt tồn về `$countTo` — chạy cùng lúc.
 *
 * @param  list<array{0: string, 1: string}>  $carts
 * @return list<Closure>
 */
function checkoutWhileCountingTasks(array $carts, int $locationId, int $variantId, int $countTo): array
{
    $tasks = [];
    foreach ($carts as $i => [$publicId, $token]) {
        $tasks[] = static function () use ($publicId, $token, $i): string {
            app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));
            $payload = C::orderPayload();
            try {
                app(Checkout::class)->placeOrder(new CheckoutRequest(new CartKey($publicId, $token), $payload['contact'], $payload['shipping_address'], 'standard', 'cod', [], null, 330_000), "count-race-{$i}-key");

                return 'ok';
            } catch (BusinessRuleViolation $exception) {
                return $exception->errorCode();
            }
        };
    }
    $tasks[] = static function () use ($locationId, $variantId, $countTo): string {
        return app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(StockAdjustmentService::class)
            ->count(Location::query()->findOrFail($locationId), $variantId, $countTo, 'Kiểm kê cuối ngày'));
    };

    return $tasks;
}

/**
 * @return array{on_hand: int, reserved: int}
 */
function lifecycleStock(int $variantId): array
{
    $level = DB::table('stock_levels')->where('variant_id', $variantId)->first();

    return ['on_hand' => (int) $level->on_hand, 'reserved' => (int) $level->reserved];
}

function lifecycleShip(int $orderId, ShipmentStatus ...$statuses): int
{
    $shipment = Shipment::query()->where('order_id', $orderId)->sole();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($shipment, $statuses): void {
        app(FulfillmentService::class)->book($shipment->id, 'T'.$shipment->id);
        foreach ($statuses as $status) {
            app(FulfillmentService::class)->updateStatus($shipment->id, $status, "setup-{$status->value}", 'staff');
        }
    });

    return $shipment->id;
}

it('nhân viên huỷ một đơn từ 4 nơi cùng lúc → huỷ đúng một lần, giữ hàng nhả đúng một lần', function () {
    $orderId = lifecycleOrder($this->variant->id, 'cod', 'lifecycle-cancel');
    expect(lifecycleStock($this->variant->id))->toBe(['on_hand' => 10, 'reserved' => 1]);

    $results = Concurrency::driver('process')->run(lifecycleTasks(4, 'cancel', $orderId));

    expect($results)->toContain('ok')
        ->and(array_count_values($results)['ok'])->toBe(1)
        ->and(DB::table('orders')->value('order_status'))->toBe('cancelled')
        ->and((int) DB::table('order_events')->where('order_id', $orderId)->where('to_status', 'cancelled')->count())->toBe(1)
        ->and(lifecycleStock($this->variant->id))->toBe(['on_hand' => 10, 'reserved' => 0]);
});

it('hãng gửi 4 webhook "đã giao" (khác mã sự kiện) cùng lúc → chuyển trạng thái một lần, tồn kho trừ một lần', function () {
    $orderId = lifecycleOrder($this->variant->id, 'cod', 'lifecycle-deliver');
    $shipmentId = lifecycleShip($orderId, ShipmentStatus::PickedUp);
    $afterPickup = lifecycleStock($this->variant->id);

    $results = Concurrency::driver('process')->run(lifecycleTasks(4, 'delivered', $shipmentId));

    expect(array_filter($results, fn (string $result): bool => $result !== 'ok'))->toBe([])
        ->and(DB::table('shipments')->value('status'))->toBe('delivered')
        // Mỗi webhook đều được lưu vết (khác mã sự kiện); chuyển trạng thái đơn chỉ một lần.
        ->and((int) DB::table('shipment_events')->where('shipment_id', $shipmentId)->where('status', 'delivered')->count())->toBe(4)
        ->and((int) DB::table('order_events')->where('order_id', $orderId)->where('type', 'fulfillment_status_changed')->where('to_status', 'delivered')->count())->toBe(1)
        ->and(lifecycleStock($this->variant->id))->toBe($afterPickup)
        ->and(lifecycleStock($this->variant->id))->toBe(['on_hand' => 9, 'reserved' => 0]);
});

it('nhận hàng trả và hoàn tiền đổi trả bị bấm 4 lần cùng lúc → nhập kho một lần, hoàn tiền một lần', function () {
    $orderId = lifecycleOrder($this->variant->id, 'fake_online', 'lifecycle-return');
    $payment = DB::table('payments')->where('order_id', $orderId)->first();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(PaymentService::class)
        ->applyCallback('fake_online', new GatewayCallback($payment->public_id, 'TXN-RETURN', GatewayCallback::PAID, Money::vnd(330_000))));
    lifecycleShip($orderId, ShipmentStatus::PickedUp, ShipmentStatus::Delivered);
    $lineId = (int) DB::table('order_lines')->where('order_id', $orderId)->value('id');
    $returnId = app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($orderId, $lineId): int {
        app(Returns::class)->request($orderId, [$lineId => 1], 'wrong_size', null, 'customer');
        $returnId = (int) DB::table('return_requests')->value('id');
        app(ReturnService::class)->transition($returnId, ReturnStatus::Approved, null, 'staff');

        return $returnId;
    });
    $returnLineId = (int) DB::table('return_lines')->where('return_request_id', $returnId)->value('id');

    $received = Concurrency::driver('process')->run(lifecycleTasks(4, 'receive', $returnId, $returnLineId));
    $resolved = Concurrency::driver('process')->run(lifecycleTasks(4, 'resolve', $returnId));

    expect(array_count_values($received)['ok'] ?? 0)->toBe(1, implode(',', $received))
        ->and(array_count_values($resolved)['ok'] ?? 0)->toBe(1, implode(',', $resolved))
        ->and(lifecycleStock($this->variant->id))->toBe(['on_hand' => 10, 'reserved' => 0])
        ->and((int) DB::table('refunds')->count())->toBe(1)
        ->and((int) DB::table('refunds')->sum('amount'))->toBe((int) DB::table('return_requests')->value('refund_amount'))
        ->and(DB::table('return_requests')->value('status'))->toBe('resolved');
});

it('nhân viên kiểm kê đúng lúc 6 khách đặt hàng → sổ tồn khớp: reserved = hàng đang giữ, tồn = tổng biến động', function () {
    DB::table('stock_levels')->where('variant_id', $this->variant->id)->update(['on_hand' => 5]);
    $carts = [];
    for ($i = 0; $i < 6; $i++) {
        $key = app(Carts::class)->create('VND')->key;
        app(Carts::class)->addLine($key, $this->variant->id, 1);
        $carts[] = [$key->publicId, $key->token];
    }
    $locationId = (int) DB::table('stock_levels')->where('variant_id', $this->variant->id)->value('location_id');

    $results = Concurrency::driver('process')->run(checkoutWhileCountingTasks($carts, $locationId, $this->variant->id, 8));
    $verified = app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(StockVerifier::class)->verify());

    expect(array_diff($results, ['ok', 'inventory.insufficient_stock', 'updated', 'unchanged']))->toBe([], implode(',', $results))
        ->and($verified['issues'])->toBe([])
        ->and(lifecycleStock($this->variant->id)['on_hand'])->toBe(8)
        ->and(lifecycleStock($this->variant->id)['reserved'])->toBe((int) DB::table('orders')->count());
});
