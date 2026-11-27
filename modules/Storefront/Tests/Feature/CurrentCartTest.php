<?php

use Modules\Cart\Contracts\Carts;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as Customers;
use Modules\Storefront\Contracts\CurrentCart;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

it('customerId: khách đăng nhập của request, khách vãng lai thì null', function () {
    expect(app(CurrentCart::class)->customerId())->toBeNull();

    $customer = Customers::webLogin($this);
    $this->get('/tai-khoan')->assertOk();

    expect(app(CurrentCart::class)->customerId())->toBe($customer->id);
});

it('trả khoá giỏ của phiên web khi đã có giỏ dùng được; chưa có giỏ thì null và không tạo giỏ', function () {
    ['s' => $s] = C::store();

    expect(app(CurrentCart::class)->currentKey())->toBeNull();

    $this->post(route('storefront.cart.add'), ['variant_id' => $s->id, 'quantity' => 2])->assertRedirect();
    $key = app(CurrentCart::class)->currentKey();

    expect($key)->not->toBeNull()
        ->and(app(Carts::class)->view($key)->lines[0]->quantity)->toBe(2);
});
