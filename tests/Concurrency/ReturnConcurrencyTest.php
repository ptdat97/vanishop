<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Modules\Cart\Contracts\Carts;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Returns\Contracts\ReturnRejected;
use Modules\Returns\Contracts\Returns;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/*
| Khách bấm gửi yêu cầu trả nhiều lần/nhiều thiết bị cùng lúc → tổng trả không vượt số đã giao (khoá dòng đơn).
*/

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

uses(DatabaseTruncation::class)->group('concurrency');

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Concurrency test cần MySQL (SQLite in-memory không chia sẻ giữa tiến trình).');
    }
});

/**
 * @return list<Closure>
 */
function returnTasks(int $count, int $orderId, int $lineId): array
{
    $tasks = [];
    for ($i = 0; $i < $count; $i++) {
        $tasks[] = static function () use ($orderId, $lineId): string {
            try {
                app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(Returns::class)->request($orderId, [$lineId => 2], 'wrong_size', null, 'customer'));

                return 'ok';
            } catch (ReturnRejected $exception) {
                return $exception->errorCode();
            }
        };
    }

    return $tasks;
}

it('4 yêu cầu trả toàn bộ gửi cùng lúc → chỉ một được tạo', function () {
    ['s' => $variant] = C::store();
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));
    $key = app(Carts::class)->create('VND')->key;
    app(Carts::class)->addLine($key, $variant->id, 2);
    $payload = C::orderPayload();
    app(Checkout::class)->placeOrder(new CheckoutRequest($key, $payload['contact'], $payload['shipping_address'], 'standard', 'cod', [], null, 600_000), 'return-concurrency-key');

    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        $shipment = Shipment::query()->sole();
        $service = app(FulfillmentService::class);
        $service->book($shipment->id, 'T1');
        $service->updateStatus($shipment->id, ShipmentStatus::PickedUp, 'e1', 'staff');
        $service->updateStatus($shipment->id, ShipmentStatus::Delivered, 'e2', 'staff');
    });

    $results = Concurrency::driver('process')->run(returnTasks(4, (int) DB::table('orders')->value('id'), (int) DB::table('order_lines')->value('id')));

    expect(array_count_values($results))->toEqualCanonicalizing(['ok' => 1, 'return.quantity_exceeded' => 3])
        ->and((int) DB::table('return_lines')->sum('quantity'))->toBe(2);
});
