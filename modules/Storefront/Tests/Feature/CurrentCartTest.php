<?php

use Modules\Cart\Contracts\Carts;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Storefront\Contracts\CurrentCart;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

it('trả khoá giỏ của phiên web khi đã có giỏ dùng được; chưa có giỏ thì null và không tạo giỏ', function () {
    ['s' => $s] = C::store();

    expect(app(CurrentCart::class)->currentKey())->toBeNull();

    $this->post(route('storefront.cart.add'), ['variant_id' => $s->id, 'quantity' => 2])->assertRedirect();
    $key = app(CurrentCart::class)->currentKey();

    expect($key)->not->toBeNull()
        ->and(app(Carts::class)->view($key)->lines[0]->quantity)->toBe(2);
});
