<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\BusinessRuleViolation;

/*
| PlaceOrder dưới tải thật (nhiều tiến trình, MySQL): không bán vượt tồn, không vượt lượt voucher,
| một giỏ chỉ thành một đơn.
*/

require_once __DIR__.'/../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

uses(DatabaseTruncation::class)->group('concurrency');

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Concurrency test cần MySQL (SQLite in-memory không chia sẻ giữa tiến trình).');
    }

    ['s' => $this->s] = C::store('lumiere', stock: 3);
});

/**
 * Tạo n giỏ, mỗi giỏ 1 × variant. Trả [publicId, token].
 *
 * @return list<array{0: string, 1: string}>
 */
function checkoutCarts(int $count, int $variantId): array
{
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));
    $carts = [];
    for ($i = 0; $i < $count; $i++) {
        $key = app(Carts::class)->create('VND')->key;
        app(Carts::class)->addLine($key, $variantId, 1);
        $carts[] = [$key->publicId, $key->token];
    }

    return $carts;
}

/**
 * @param  list<array{0: string, 1: string, 2: string}>  $orders  [publicId, token, idempotencyKey]
 * @param  list<string>  $vouchers
 * @return list<Closure>
 */
function placeOrderTasks(array $orders, int $expectedTotal, array $vouchers = []): array
{
    $tasks = [];
    foreach ($orders as [$publicId, $token, $idempotencyKey]) {
        $tasks[] = static function () use ($publicId, $token, $idempotencyKey, $expectedTotal, $vouchers): string {
            app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));
            $payload = C::orderPayload();

            try {
                app(Checkout::class)->placeOrder(new CheckoutRequest(
                    new CartKey($publicId, $token), $payload['contact'], $payload['shipping_address'], 'standard', 'cod', $vouchers, null, $expectedTotal,
                ), $idempotencyKey);

                return 'ok';
            } catch (BusinessRuleViolation $exception) {
                return $exception->errorCode();
            }
        };
    }

    return $tasks;
}

it('8 khách cùng đặt SKU chỉ còn 3 → đúng 3 đơn, tồn giữ đúng 3', function () {
    $carts = checkoutCarts(8, $this->s->id);
    $orders = array_map(fn (array $cart, int $i): array => [...$cart, "key-{$i}-abcdef"], $carts, array_keys($carts));

    $results = Concurrency::driver('process')->run(placeOrderTasks($orders, 330_000));
    $counts = array_count_values($results);

    expect($counts['ok'] ?? 0)->toBe(3)
        ->and(array_diff(array_keys($counts), ['ok', 'inventory.insufficient_stock', 'checkout.invalid']))->toBe([])
        ->and((int) DB::table('orders')->count())->toBe(3)
        ->and((int) DB::table('stock_levels')->where('variant_id', $this->s->id)->value('reserved'))->toBe(3)
        // Cùng SĐT, đặt song song → đúng một hồ sơ khách (unique phone_active), mọi đơn gắn khách đó.
        ->and((int) DB::table('customers')->count())->toBe(1)
        ->and(DB::table('orders')->distinct()->pluck('customer_id')->all())->toBe([(int) DB::table('customers')->value('id')]);
});

it('voucher còn 2 lượt, 6 khách cùng dùng → đúng 2 đơn có giảm giá', function () {
    DB::table('stock_levels')->update(['on_hand' => 100]);
    C::promotion(['name' => 'Giảm 10%'], ['FLASH' => 2]);
    $carts = checkoutCarts(6, $this->s->id);
    $orders = array_map(fn (array $cart, int $i): array => [...$cart, "key-{$i}-voucher"], $carts, array_keys($carts));

    $results = Concurrency::driver('process')->run(placeOrderTasks($orders, 300_000, ['FLASH']));
    $counts = array_count_values($results);

    expect($counts['ok'] ?? 0)->toBe(2)
        ->and(array_diff(array_keys($counts), ['ok', 'promotion.voucher_exhausted', 'checkout.voucher_invalid']))->toBe([])
        ->and((int) DB::table('vouchers')->where('code', 'FLASH')->value('used_count'))->toBe(2)
        ->and((int) DB::table('orders')->where('discount_amount', 30_000)->count())->toBe(2)
        ->and((int) DB::table('stock_levels')->where('variant_id', $this->s->id)->value('reserved'))->toBe(2);
});

it('một giỏ gửi đặt hàng 5 lần song song (khác key) → một đơn', function () {
    [$cart] = checkoutCarts(1, $this->s->id);
    $orders = array_map(fn (int $i): array => [...$cart, "same-cart-{$i}-key"], range(1, 5));

    $results = Concurrency::driver('process')->run(placeOrderTasks($orders, 330_000));

    expect(array_count_values($results))->toEqualCanonicalizing(['cart.closed' => 4, 'ok' => 1])
        ->and((int) DB::table('orders')->count())->toBe(1);
});
