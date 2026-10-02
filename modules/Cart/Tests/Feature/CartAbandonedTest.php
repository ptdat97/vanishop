<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Modules\Cart\Events\CartAbandoned;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';
require_once __DIR__.'/../../../Customer/Tests/Feature/CustomerTestHelpers.php';

beforeEach(function () {
    H::fakeOtp();
    ['s' => $this->s] = C::store();
    $this->events = [];
    Event::listen(CartAbandoned::class, function (CartAbandoned $event): void {
        $this->events[] = $event;
    });
    $this->detect = fn () => $this->artisan('vani:cart:detect-abandoned')->assertSuccessful();
});

it('giỏ của khách còn hàng, không hoạt động quá ngưỡng → phát một lần; quay lại rồi bỏ tiếp → phát lại', function () {
    $token = H::login($this);
    $cartId = $this->getJson(H::API.'/me/cart', H::auth($token))->json('data.id');
    $this->postJson(H::API."/carts/{$cartId}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], H::auth($token))->assertOk();

    ($this->detect)();
    expect($this->events)->toBe([]);

    $this->travel(61)->minutes();
    ($this->detect)();
    ($this->detect)();
    expect($this->events)->toHaveCount(1)
        ->and($this->events[0]->cartPublicId)->toBe($cartId)
        ->and($this->events[0]->itemCount)->toBe(2)
        ->and($this->events[0]->subtotal)->toBe(600_000);

    $this->travel(5)->minutes(); // khách quay lại sau đó
    $this->postJson(H::API."/carts/{$cartId}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], H::auth($token))->assertOk();
    $this->travel(61)->minutes();
    ($this->detect)();
    expect($this->events)->toHaveCount(2)->and($this->events[1]->itemCount)->toBe(3);
});

it('giỏ vãng lai hoặc giỏ trống không phát', function () {
    $created = $this->postJson(H::API.'/carts')->assertCreated();
    $this->postJson(H::API."/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], ['X-Vani-Cart-Token' => $created->json('meta.token')])->assertOk();
    $token = H::login($this);
    $this->getJson(H::API.'/me/cart', H::auth($token))->assertOk();

    $this->travel(2)->hours();
    ($this->detect)();

    expect($this->events)->toBe([])
        ->and(DB::table('carts')->whereNotNull('abandoned_notified_at')->count())->toBe(0);
});
