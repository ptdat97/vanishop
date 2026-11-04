<?php

use Modules\Cart\Persistence\Models\Cart;
use Modules\Cart\Persistence\Models\CartLine;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Persistence\Models\Order;

require_once __DIR__.'/../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Bất biến chọn dòng giỏ (reference-parity §3.8), với mọi cách chọn khác rỗng:
|   S1 đơn chỉ chứa đúng các dòng đã chọn (variant + số lượng).
|   S2 dòng chưa chọn còn nguyên trong giỏ; số lượng của giỏ trước = đơn + giỏ sau.
|   S3 giỏ chỉ chuyển "converted" khi mọi dòng đã vào đơn.
*/

dataset('selections', [
    'chỉ S' => [['s']],
    'chỉ M' => [['m']],
    'cả hai' => [['s', 'm']],
]);

it('đơn chỉ chứa dòng đã chọn, phần còn lại ở lại giỏ', function (array $chosen) {
    $variants = C::store();
    $api = '/api/storefront/v1';
    $created = $this->postJson("{$api}/carts")->assertCreated();
    $cart = $created->json('data.id');
    $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];

    $quantities = ['s' => 1, 'm' => 2];
    $lineIds = [];
    foreach ($quantities as $name => $quantity) {
        $lines = $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $variants[$name]->id, 'quantity' => $quantity], $headers)->assertOk()->json('data.lines');
        $lineIds[$name] = end($lines)['id'];
    }
    $this->putJson("{$api}/carts/{$cart}/selection", ['line_ids' => array_map(fn (string $name): int => $lineIds[$name], $chosen)], $headers)->assertOk();

    $total = $this->postJson("{$api}/checkout/{$cart}/quote", [], $headers)->json('data.total.amount');
    $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => $total]), [...$headers, 'Idempotency-Key' => 'invariant-'.implode('-', $chosen)])->assertCreated();

    $ordered = Order::query()->withoutGlobalScopes()->with('lines')->sole()->lines->pluck('quantity', 'variant_id')->all();
    $remaining = CartLine::query()->whereIn('cart_id', Cart::query()->where('public_id', $cart)->select('id'))->pluck('quantity', 'variant_id')->all();
    $expectedOrdered = [];
    $expectedRemaining = [];
    foreach ($quantities as $name => $quantity) {
        in_array($name, $chosen, true) ? $expectedOrdered[$variants[$name]->id] = $quantity : $expectedRemaining[$variants[$name]->id] = $quantity;
    }

    expect($ordered)->toEqualCanonicalizing($expectedOrdered)
        ->and($remaining)->toEqualCanonicalizing($expectedRemaining)
        ->and(array_sum($ordered) + array_sum($remaining))->toBe(array_sum($quantities))
        ->and(Cart::query()->where('public_id', $cart)->value('status')->value)->toBe($remaining === [] ? 'converted' : 'active');
})->with('selections');
