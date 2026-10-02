<?php

use Illuminate\Support\Facades\Event;
use Modules\Cart\Contracts\CartRejected;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Cart\Events\CartUpdated;
use Modules\Cart\Persistence\Models\Cart;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Facades\Hook;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Inventory/Tests/Feature/InventoryTestHelpers.php';

beforeEach(function () {
    config(['vanishop.cart.max_line_quantity' => 5, 'vanishop.cart.max_lines' => 3]);
    $this->brand = Brand::factory()->create();
    [$this->s, $this->m, $this->l] = P::variants(T::product($this->brand->id), ['S', 'M', 'L']);
    $this->base = P::priceList(['code' => 'base'], [$this->s->id => [500_000], $this->m->id => [500_000], $this->l->id => [500_000]]);
    $this->warehouse = I::location();
    I::stock($this->warehouse, $this->s->id, 10);
    I::stock($this->warehouse, $this->m->id, 2);
    I::stock($this->warehouse, $this->l->id, 10);
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));
    $this->carts = app(Carts::class);
});

function rejectedCode(Closure $call): ?string
{
    try {
        $call();
    } catch (CartRejected $exception) {
        return $exception->errorCode();
    }

    return null;
}

it('cộng dồn khi thêm cùng variant; chặn vượt số có thể bán và giới hạn dòng', function () {
    $key = $this->carts->create('VND')->key;

    $this->carts->addLine($key, $this->s->id, 2);
    $view = $this->carts->addLine($key, $this->s->id, 1);

    expect($view->lines)->toHaveCount(1)
        ->and($view->lines[0]->quantity)->toBe(3)
        ->and($view->subtotal->amount)->toBe(1_500_000)
        ->and(rejectedCode(fn () => $this->carts->addLine($key, $this->m->id, 3)))->toBe('cart.insufficient_stock')
        ->and(rejectedCode(fn () => $this->carts->addLine($key, $this->s->id, 3)))->toBe('cart.quantity_limit')
        ->and($this->carts->view($key)->lines[0]->quantity)->toBe(3);
});

it('giới hạn số dòng trong giỏ', function () {
    config(['vanishop.cart.max_lines' => 2]);
    $carts = app(Carts::class);
    $key = $carts->create('VND')->key;
    $carts->addLine($key, $this->s->id, 1);
    $carts->addLine($key, $this->m->id, 1);

    expect(rejectedCode(fn () => $carts->addLine($key, $this->l->id, 1)))->toBe('cart.too_many_lines');
});

it('từ chối variant không bán được: chưa có giá, sản phẩm nháp', function () {
    $key = $this->carts->create('VND')->key;
    [$noPrice] = P::variants(T::product($this->brand->id), ['XL']);
    [$draft] = P::variants(T::product($this->brand->id, ['status' => 'draft']), ['XL']);
    foreach ([$noPrice, $draft] as $variant) {
        expect(rejectedCode(fn () => $this->carts->addLine($key, $variant->id, 1)))->toBe('cart.variant_unavailable');
    }
});

it('sửa số lượng, xoá bằng số lượng 0; giảm số lượng luôn được phép', function () {
    $key = $this->carts->create('VND')->key;
    $lineId = $this->carts->addLine($key, $this->s->id, 4)->lines[0]->id;

    expect($this->carts->updateLine($key, $lineId, 2)->lines[0]->quantity)->toBe(2)
        ->and(rejectedCode(fn () => $this->carts->updateLine($key, $lineId, 11)))->toBe('cart.quantity_limit')
        ->and($this->carts->updateLine($key, $lineId, 0)->lines)->toBe([])
        ->and(rejectedCode(fn () => $this->carts->removeLine($key, $lineId)))->toBe('cart.line_not_found');
});

it('báo giá đổi, hết hàng, ngừng bán trên dòng giỏ; subtotal bỏ dòng không bán được', function () {
    $key = $this->carts->create('VND')->key;
    $this->carts->addLine($key, $this->s->id, 1);
    $this->carts->addLine($key, $this->m->id, 2);
    $this->carts->addLine($key, $this->l->id, 1);

    P::priceList(['code' => 'sale', 'type' => 'sale', 'priority' => 10], [$this->s->id => [400_000]]);
    I::stock($this->warehouse, $this->m->id, 1);
    T::seed(fn () => $this->l->update(['status' => 'inactive']));

    $view = $this->carts->view($key);
    $issues = collect($view->lines)->mapWithKeys(fn ($line) => [$line->variantId => $line->issues]);

    expect($issues[$this->s->id])->toBe(['price_changed'])
        ->and($issues[$this->m->id])->toBe(['insufficient_stock'])
        ->and($issues[$this->l->id])->toBe(['unavailable'])
        ->and($view->subtotal->amount)->toBe(400_000 + 1_000_000)
        ->and($view->isCheckoutReady())->toBeFalse();
});

it('token sai và giỏ đã đóng', function () {
    $created = $this->carts->create('VND');
    $wrong = new CartKey($created->key->publicId, 'sai-token');

    expect(rejectedCode(fn () => $this->carts->view($wrong)))->toBe('cart.not_found');

    Cart::query()->where('public_id', $created->key->publicId)->update(['status' => 'converted']);
    expect(rejectedCode(fn () => $this->carts->addLine($created->key, $this->s->id, 1)))->toBe('cart.closed');
});

it('plugin chặn dòng giỏ qua hook vani.cart.validate_line', function () {
    Hook::onValidate('vani.cart.validate_line', fn ($draft) => $draft->quantity > 2 ? ['Mỗi khách tối đa 2 sản phẩm này.'] : []);
    $key = $this->carts->create('VND')->key;
    $this->carts->addLine($key, $this->s->id, 2);

    try {
        $this->carts->addLine($key, $this->s->id, 1);
        $this->fail('Phải bị từ chối');
    } catch (CartRejected $exception) {
        expect($exception->errorCode())->toBe('cart.line_rejected')
            ->and($exception->getMessage())->toBe('Mỗi khách tối đa 2 sản phẩm này.');
    }
});

it('gộp giỏ: cộng dồn có kẹp, bỏ dòng không bán được, đóng giỏ nguồn', function () {
    $guest = $this->carts->create('VND')->key;
    $member = $this->carts->create('VND')->key;
    $this->carts->addLine($guest, $this->s->id, 4);
    $this->carts->addLine($guest, $this->m->id, 2);
    $this->carts->addLine($guest, $this->l->id, 1);
    $this->carts->addLine($member, $this->s->id, 3);
    $this->carts->addLine($member, $this->m->id, 1);
    T::seed(fn () => $this->l->update(['status' => 'inactive']));

    $view = $this->carts->merge($guest, $member);
    $quantities = collect($view->lines)->pluck('quantity', 'variantId')->all();

    expect($quantities)->toBe([$this->s->id => 5, $this->m->id => 2])
        ->and($this->carts->view($guest)->status)->toBe('merged')
        ->and(rejectedCode(fn () => $this->carts->addLine($guest, $this->s->id, 1)))->toBe('cart.closed');
});

it('phát CartUpdated sau mỗi thay đổi', function () {
    Event::fake([CartUpdated::class]);
    $key = $this->carts->create('VND')->key;
    $this->carts->addLine($key, $this->s->id, 1);

    Event::assertDispatched(CartUpdated::class, fn (CartUpdated $event) => $event->cartId === $key->publicId);
});

it('dọn giỏ không hoạt động, giữ giỏ đã đặt hàng', function () {
    $old = $this->carts->create('VND')->key;
    $this->carts->addLine($old, $this->s->id, 1);
    $converted = $this->carts->create('VND')->key;
    $fresh = $this->carts->create('VND')->key;
    Cart::query()->whereIn('public_id', [$old->publicId, $converted->publicId])->update(['last_activity_at' => now()->subDays(31)]);
    Cart::query()->where('public_id', $converted->publicId)->update(['status' => 'converted']);

    $this->artisan('vani:cart:prune')->assertSuccessful();

    expect(Cart::query()->pluck('public_id')->sort()->values()->all())->toBe(collect([$converted->publicId, $fresh->publicId])->sort()->values()->all())
        ->and(DB::table('cart_lines')->count())->toBe(0);
});
