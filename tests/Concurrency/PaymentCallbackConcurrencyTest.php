<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Modules\Cart\Contracts\Carts;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Contracts\Data\GatewayCallback;
use Modules\Payment\Tests\Feature\Fixtures\FakeOnlineGateway;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;

/*
| Cổng gửi cùng một IPN nhiều lần gần như đồng thời → chỉ ghi nhận một lần (unique transaction + khoá payment).
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
function duplicateCallbackTasks(int $count, string $paymentId): array
{
    $tasks = [];
    for ($i = 0; $i < $count; $i++) {
        $tasks[] = static function () use ($paymentId): string {
            $callback = new GatewayCallback($paymentId, 'TXN-SAME', GatewayCallback::PAID, Money::vnd(330_000));

            return app(CurrentContext::class)->runAs(ContextScope::system('test'), fn () => app(PaymentService::class)->applyCallback('fake_online', $callback)) ? 'applied' : 'duplicate';
        };
    }

    return $tasks;
}

it('6 IPN trùng đến cùng lúc → ghi nhận đúng một lần', function () {
    app()->tag([FakeOnlineGateway::class], GatewayRegistry::TAG);
    ['brand' => $brand, 'channel' => $channel, 's' => $variant] = C::store();
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), $channel->id, [$brand->id], 'vi'));
    $key = app(Carts::class)->create('VND')->key;
    app(Carts::class)->addLine($key, $variant->id, 1);
    $payload = C::orderPayload();
    $result = app(Checkout::class)->placeOrder(new CheckoutRequest($key, $payload['contact'], $payload['shipping_address'], 'standard', 'fake_online', [], null, 330_000), 'ipn-concurrency-key');

    $results = Concurrency::driver('process')->run(duplicateCallbackTasks(6, $result->payment->publicId));

    expect(array_count_values($results))->toEqualCanonicalizing(['applied' => 1, 'duplicate' => 5])
        ->and((int) DB::table('payment_transactions')->where('type', 'callback')->count())->toBe(1)
        ->and(DB::table('payments')->value('status'))->toBe('paid')
        ->and(DB::table('orders')->value('order_status'))->toBe('confirmed')
        ->and((int) DB::table('order_events')->where('type', 'payment_status_changed')->count())->toBe(1);
});
