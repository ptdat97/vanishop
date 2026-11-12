<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Modules\Cart\Contracts\Carts;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Ordering\Application\OrderCommands;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;
use Tests\Support\KeepsReferenceData;

/*
| Khách huỷ đơn đúng lúc cổng báo đã thanh toán (stabilization C2). Kết cục nào cũng được, miễn nhất quán:
| đơn huỷ mà tiền đã thu → phải có yêu cầu hoàn tiền; đơn không huỷ → đã thanh toán và được xác nhận. Không có
| "đơn huỷ, tiền thu, không hoàn", không hoàn hai lần, và không tác vụ nào lỗi ngoài nghiệp vụ (IPN không được trả lỗi
| chỉ vì khách vừa huỷ).
*/

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

uses(DatabaseTruncation::class, KeepsReferenceData::class)->group('concurrency');

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Concurrency test cần MySQL (SQLite in-memory không chia sẻ giữa tiến trình).');
    }
});

/**
 * Một cặp tác vụ chạy song song: khách huỷ đơn, cổng báo đã thanh toán. Khai báo ngoài test để closure không gắn với
 * class test (tiến trình con không nạp class đó).
 *
 * @return list<Closure>
 */
function cancelAndPayTasks(int $orderId, string $paymentId): array
{
    $tasks = [];
    $tasks[] = static function () use ($orderId): string {
        try {
            app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(OrderCommands::class)->cancel($orderId, 'Đổi ý', 'customer'));

            return 'cancelled';
        } catch (Throwable $exception) {
            return 'cancel:'.class_basename($exception);
        }
    };
    $tasks[] = static function () use ($paymentId): string {
        try {
            $callback = new GatewayCallback($paymentId, "TXN-{$paymentId}", GatewayCallback::PAID, Money::vnd(330_000));

            return app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(PaymentService::class)->applyCallback('fake_online', $callback)) ? 'paid' : 'duplicate';
        } catch (Throwable $exception) {
            return 'pay:'.class_basename($exception).':'.$exception->getMessage();
        }
    };

    return $tasks;
}

it('huỷ đơn và IPN "đã thanh toán" đến cùng lúc (6 đơn) → không đơn nào huỷ mà đã thu tiền nhưng không hoàn', function () {
    app(Extensions::class)->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    ['s' => $variant] = C::store('lumiere', 50);
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));

    $tasks = [];
    for ($i = 0; $i < 6; $i++) {
        $key = app(Carts::class)->create('VND')->key;
        app(Carts::class)->addLine($key, $variant->id, 1);
        $payload = C::orderPayload();
        $placed = app(Checkout::class)->placeOrder(new CheckoutRequest($key, $payload['contact'], $payload['shipping_address'], 'standard', 'fake_online', [], null, 330_000), "race-cancel-pay-{$i}");
        $orderId = (int) DB::table('orders')->latest('id')->value('id');
        $paymentId = $placed->payment->publicId;
        array_push($tasks, ...cancelAndPayTasks($orderId, $paymentId));
    }

    $results = Concurrency::driver('process')->run($tasks);

    $broken = [];
    foreach (DB::table('orders')->get() as $order) {
        $payment = DB::table('payments')->where('order_id', $order->id)->first();
        $refunds = (int) DB::table('refunds')->where('payment_id', $payment->id)->count();
        if ((int) DB::table('refunds')->where('payment_id', $payment->id)->sum('amount') > $payment->amount) {
            $broken[] = "{$order->number}: hoàn nhiều hơn số đã thu";
        }
        $collected = in_array($payment->status, ['paid', 'partially_refunded', 'refunded'], true);
        if ($order->order_status === 'cancelled' && $collected && $refunds === 0) {
            $broken[] = "{$order->number}: huỷ + đã thu + không hoàn";
        }
        if ($order->order_status !== 'cancelled' && ! ($collected && $order->payment_status === 'paid')) {
            $broken[] = "{$order->number}: không huỷ nhưng {$order->order_status}/{$payment->status}";
        }
    }

    expect($broken)->toBe([], implode(' | ', $results))
        ->and(array_filter($results, fn (string $result): bool => str_contains($result, ':') && $result !== 'cancel:OrderActionRejected'))->toBe([], 'Không tác vụ nào được lỗi ngoài nghiệp vụ');
});
