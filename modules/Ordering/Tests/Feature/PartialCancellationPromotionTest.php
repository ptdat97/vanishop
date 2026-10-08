<?php

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Promotion\Contracts\PromotionRule;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Promotion\Tests\Fixtures\FirstOrderFixtureRule;
use Modules\Promotion\Tests\Fixtures\MinSubtotalFixtureRule;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Roadmap Phase 2: tính lại khuyến mãi theo ngưỡng sau huỷ một phần (order §2.1). Lỗi shop → giữ ưu đãi; khách bớt
| hàng → thu hồi phần không còn đủ điều kiện, tối đa bằng tiền phần huỷ.
*/

function promotionWithRule(string $ruleType, array $config, int $amountOff): Promotion
{
    $promotion = C::promotion(['name' => "Giảm {$amountOff}", 'action_type' => 'amount_off', 'action_config' => ['amount' => $amountOff]]);
    T::seed(fn () => $promotion->rules()->create(['rule_type' => $ruleType, 'config' => $config]));

    return $promotion;
}

beforeEach(function () {
    FirstOrderFixtureRule::$passes = true;
    app(Extensions::class)->tag([MinSubtotalFixtureRule::class, FirstOrderFixtureRule::class], PromotionRule::TAG);
    config(['vani.shipping-flat-rate.fee' => 0]);
    ['s' => $this->s, 'm' => $this->m] = C::store(); // S 300.000đ, M 200.000đ
    $this->place = function (array $quantities, int $expected): Order {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        foreach ($quantities as $variant => $quantity) {
            $this->postJson("/api/storefront/v1/carts/{$cart}/lines", ['variant_id' => $this->{$variant}->id, 'quantity' => $quantity], $headers)->assertOk();
        }
        $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['payment_method' => 'cod', 'expected_total' => $expected]), [...$headers, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();

        return Order::query()->latest('id')->first();
    };
    $this->cancel = function (Order $order, string $variant, int $quantity, string $cause) {
        $line = $order->lines()->where('variant_id', $this->{$variant}->id)->sole();

        return $this->post("/admin/orders/orders/{$order->id}/cancel-lines", ['lines' => [$line->id => $quantity], 'reason' => 'x', 'cause' => $cause, 'lock_version' => $order->fresh()->lock_version]);
    };
    $this->codAmount = fn (Order $order): int => (int) DB::table('payments')->where('order_id', $order->id)->value('amount');
    $this->usage = fn (Promotion $promotion, Order $order): int => (int) DB::table('promotion_usages')->where('promotion_id', $promotion->id)->where('order_id', $order->id)->value('discount_amount');
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'orders.manage', 'orders.cancel']), 'staff');
});

it('lỗi shop: giữ nguyên ưu đãi (chỉ bớt phần giảm của hàng huỷ); khách bớt hàng dưới ngưỡng: thu hồi phần ưu đãi còn lại', function () {
    $promotion = promotionWithRule('fixture_min_subtotal', ['min' => 500_000], 100_000);

    // S 300k + M 200k = 500k, giảm 100k (chia 60k/40k) → 400k. Huỷ M: phần huỷ 160k.
    $shop = ($this->place)(['s' => 1, 'm' => 1], 400_000);
    ($this->cancel)($shop, 'm', 1, 'shop')->assertSessionHasNoErrors();
    expect($shop->fresh()->total_amount)->toBe(240_000)
        ->and(($this->codAmount)($shop))->toBe(240_000)
        ->and(($this->usage)($promotion, $shop))->toBe(60_000)
        ->and(DB::table('order_adjustments')->where('order_id', $shop->id)->where('type', 'promotion_clawback')->exists())->toBeFalse();

    // Khách bớt M → còn 300k < 500k: thu hồi 60k đang giảm cho S. Tổng 300k, COD giảm ròng 100k.
    $customer = ($this->place)(['s' => 1, 'm' => 1], 400_000);
    ($this->cancel)($customer, 'm', 1, 'customer')->assertSessionHasNoErrors();
    $customer->refresh();
    $sLine = $customer->lines()->where('variant_id', $this->s->id)->sole();
    expect([$customer->total_amount, $customer->discount_amount, $sLine->discount_amount, $sLine->total_amount])->toBe([300_000, 0, 0, 300_000])
        ->and($sLine->tax_amount)->toBe(27_273) // VAT 10% đã gồm trên 300k
        ->and(($this->codAmount)($customer))->toBe(300_000)
        ->and(($this->usage)($promotion, $customer))->toBe(0)
        ->and(DB::table('order_adjustments')->where('order_id', $customer->id)->where('type', 'promotion_clawback')->value('amount'))->toBe(60_000)
        ->and($customer->total_amount)->toBe($customer->lines()->sum('total_amount') + $customer->shipping_amount);

    $event = json_decode(DB::table('order_events')->where('order_id', $customer->id)->where('type', 'lines_cancelled')->value('data'), true);
    expect($event['cause'])->toBe('customer')->and($event['totals'])->toMatchArray(['total' => 160_000, 'promotion_clawback' => 60_000, 'net' => 100_000]);
    expect(app(OrderReader::class)->cancellations($customer->id)[0])->toMatchArray(['amount' => 100_000, 'promotion_clawback' => 60_000]);
});

it('khách bớt hàng nhưng vẫn đủ ngưỡng → không thu hồi; lần bớt sau xuống dưới ngưỡng → chỉ thu phần còn lại, không thu trùng', function () {
    $promotion = promotionWithRule('fixture_min_subtotal', ['min' => 500_000], 100_000);
    // S 300k + 2×M 400k = 700k, giảm 100k → 600k.
    $order = ($this->place)(['s' => 1, 'm' => 2], 600_000);
    $before = $order->lines()->pluck('discount_amount', 'variant_id')->all();

    ($this->cancel)($order, 'm', 1, 'customer')->assertSessionHasNoErrors(); // còn 500k ≥ 500k
    $order->refresh();
    expect(DB::table('order_adjustments')->where('order_id', $order->id)->where('type', 'promotion_clawback')->exists())->toBeFalse()
        ->and($order->discount_amount)->toBe($before[$this->s->id] + intdiv($before[$this->m->id], 2) + $before[$this->m->id] % 2);

    ($this->cancel)($order, 'm', 1, 'customer')->assertSessionHasNoErrors(); // còn 300k
    $order->refresh();
    expect($order->discount_amount)->toBe(0)
        ->and($order->total_amount)->toBe(300_000)
        ->and(($this->usage)($promotion, $order))->toBe(0)
        ->and((int) DB::table('order_adjustments')->where('order_id', $order->id)->where('type', 'promotion_clawback')->sum('amount'))->toBe($before[$this->s->id]);
});

it('thu hồi không vượt tiền phần huỷ: khách không bao giờ phải trả nhiều hơn trước khi bớt hàng', function () {
    promotionWithRule('fixture_min_subtotal', ['min' => 600_000], 300_000);
    // 3×M = 600k, giảm 300k → 300k. Bớt 1: phần huỷ 100k; giảm còn lại 200k không còn đủ điều kiện → chỉ thu 100k.
    $order = ($this->place)(['m' => 3], 300_000);
    ($this->cancel)($order, 'm', 1, 'customer')->assertSessionHasNoErrors();

    $order->refresh();
    expect([$order->total_amount, $order->discount_amount, ($this->codAmount)($order)])->toBe([300_000, 100_000, 300_000])
        ->and(DB::table('order_adjustments')->where('order_id', $order->id)->where('type', 'promotion_clawback')->value('amount'))->toBe(100_000)
        ->and($order->total_amount)->toBe($order->lines()->sum('total_amount') + $order->shipping_amount);
});

it('rule không phụ thuộc dòng hàng (vd. đơn đầu tiên) không bị kiểm tra lại → không thu hồi', function () {
    $promotion = promotionWithRule('fixture_first_order', [], 100_000);
    $order = ($this->place)(['s' => 1, 'm' => 1], 400_000);
    FirstOrderFixtureRule::$passes = false; // như "đơn đầu tiên" sau khi đơn đã tồn tại

    ($this->cancel)($order, 'm', 1, 'customer')->assertSessionHasNoErrors();
    expect($order->fresh()->total_amount)->toBe(240_000)
        ->and(($this->usage)($promotion, $order))->toBe(60_000);
});

it('Admin từ chối nguyên nhân lạ (không gửi = lỗi shop)', function () {
    $order = ($this->place)(['s' => 1, 'm' => 1], 500_000);
    ($this->cancel)($order, 'm', 1, 'khac')->assertSessionHasErrors('cause');
});
