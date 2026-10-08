<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Ordering\Application\OrderCommands;
use Modules\Ordering\Contracts\Data\LineCancellationCause;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Roadmap Phase 8: tách tầng giá trên đơn — niêm yết → giảm giá bán (bảng giá sale/thành viên) → tạm tính → khuyến mãi
| tự động → mã giảm giá → giảm khác → phí giao → tổng (VAT đã gồm). Tính từ snapshot; nguồn bảng giá lưu trên dòng.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(); // base S 300.000
    P::priceList(['code' => 'sale-he', 'type' => 'sale', 'priority' => 10], [$this->s->id => [240_000, 300_000]]);
    C::promotion(['name' => 'Giảm 10% toàn shop', 'action_type' => 'percent_off', 'action_config' => ['basis_points' => 1000], 'priority' => 10]);
    C::promotion(['name' => 'Mã giảm 20k', 'action_type' => 'amount_off', 'action_config' => ['amount' => 20_000], 'priority' => 0], ['GIAM20' => null]);
    $this->place = function (): Order {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("/api/storefront/v1/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $headers)->assertOk();
        // 2 × 240k = 480k; −10% (48k) = 432k; −20k mã = 412k; + 30k phí giao = 442k.
        $this->token = $this->postJson("/api/storefront/v1/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 442_000, 'voucher_codes' => ['GIAM20']]), [...$headers, 'Idempotency-Key' => "k-{$cart}"])
            ->assertCreated()->json('data.access_token');

        return Order::query()->latest('id')->first();
    };
});

it('đơn: niêm yết, giảm giá bán theo bảng giá, khuyến mãi tự động, mã giảm giá, phí giao, thuế — cộng lại khớp tổng', function () {
    $order = ($this->place)();
    $pricing = app(OrderReader::class)->priceBreakdown($order->id);

    expect($order->lines()->value('price_list_code'))->toBe('sale-he')
        ->and($pricing->toArray())->toMatchArray([
            'list_amount' => 600_000, 'markdown' => 120_000, 'subtotal' => 480_000,
            'promotion_discount' => 48_000, 'voucher_discount' => 20_000, 'other_discount' => 0,
            'shipping' => 30_000, 'total' => 442_000, 'price_lists' => [['code' => 'sale-he', 'markdown' => 120_000]],
        ])
        ->and(collect($pricing->promotions)->firstWhere('voucher', true))->toMatchArray(['code' => 'GIAM20', 'amount' => 20_000])
        ->and($pricing->listAmount - $pricing->markdown)->toBe($pricing->subtotal)
        ->and($pricing->subtotal - $pricing->promotionDiscount - $pricing->voucherDiscount - $pricing->otherDiscount + $pricing->shipping)->toBe($pricing->total)
        ->and($pricing->taxIncluded)->toBe($order->tax_amount);
});

it('hiển thị: trang đơn storefront và Admin có từng tầng giá', function () {
    $order = ($this->place)();

    $html = $this->withSession(["vani.orders.{$order->public_id}" => ['token' => $this->token]])->get("/don-hang/{$order->public_id}")->assertOk()->getContent();
    expect($html)->toContain('Giá niêm yết')->toContain('600.000')->toContain('Giảm giá bán')->toContain('120.000')
        ->toContain('Khuyến mãi')->toContain('48.000')->toContain('Mã giảm giá')->toContain('GIAM20');

    $this->actingAs(T::staff(['admin.access', 'orders.view']), 'staff');
    $this->get("/admin/orders/orders/{$order->id}")->assertInertia(fn (Assert $page) => $page
        ->where('order.pricing.markdown', 120_000)->where('order.pricing.voucher_discount', 20_000)->where('order.pricing.total', 442_000));
});

it('sau huỷ một phần (khách bớt hàng, có thu hồi) các tầng vẫn cân; payload tích hợp có pricing', function () {
    $order = ($this->place)();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () use ($order) {
        $line = $order->lines()->sole();
        app(OrderCommands::class)->cancelLines($order->id, [$line->id => 1], 'khách bớt', $order->fresh()->lock_version, LineCancellationCause::Customer);
    });
    $pricing = app(OrderReader::class)->priceBreakdown($order->id);
    $order->refresh();

    expect($pricing->subtotal)->toBe(240_000)->and($pricing->listAmount)->toBe(300_000)
        ->and($pricing->subtotal - $pricing->promotionDiscount - $pricing->voucherDiscount - $pricing->otherDiscount + $pricing->shipping)->toBe($order->total_amount)
        ->and($pricing->promotionDiscount + $pricing->voucherDiscount + $pricing->otherDiscount)->toBe($order->discount_amount);

    $payload = json_decode((string) DB::table('integration_events')->where('aggregate_id', $order->number)->where('event_type', 'order.created')->value('payload'), true);
    expect(($payload['data'] ?? $payload)['order']['pricing'])->toMatchArray(['list_amount' => 600_000, 'voucher_discount' => 20_000]);
});
