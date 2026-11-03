<?php

use Modules\Catalog\Contracts\CatalogReader;
use Modules\Checkout\Contracts\Data\Adjustment;
use Modules\Checkout\Contracts\Data\TotalsContext;
use Modules\Checkout\Contracts\Data\TotalsLine;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Domain\Hooks\HookNotDeclared;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Pricing\Contracts\Data\ResolvedPrice;
use Modules\Shared\Domain\Money\Money;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Điểm hook tại kết quả service nghiệp vụ (ADR-036): hook_filter('vani.service.<module>.<kết quả>', ...).
*/

beforeEach(function () {
    ['s' => $this->s] = C::store();
    $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $this->cartId = $created->json('data.id');
    $this->headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $this->postJson("/api/storefront/v1/carts/{$this->cartId}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $this->headers)->assertOk();
    $this->quote = fn () => $this->postJson("/api/storefront/v1/checkout/{$this->cartId}/quote", [], $this->headers)->assertOk();
});

/**
 * Listener của plugin `vani.cod` (đang bật): giảm $amount trên mọi dòng và ghi một Adjustment.
 */
function discountEveryLine(int $amount): void
{
    app(HookManager::class)->onFilter('vani.service.checkout.totals', function (TotalsContext $context) use ($amount): TotalsContext {
        return $context
            ->withLines(array_map(fn (TotalsLine $line): TotalsLine => $line->withDiscount($line->discount->add($context->money($amount))), $context->lines))
            ->withAdjustment(new Adjustment('loyalty', 'vani.cod', null, 'Đổi điểm', $context->money(-$amount)));
    }, 10, 'vani.cod');
}

it('helper hook_filter/add_hook_filter đi qua HookManager: hook phải khai báo', function () {
    add_hook_filter('vani.service.catalog.category_tree', fn (array $tree): array => [...$tree, ['slug' => 'them']]);

    expect(hook_filter('vani.service.catalog.category_tree', [], 'vi'))->toBe([['slug' => 'them']])
        ->and(fn () => hook_filter('vani.service.khong.khai.bao', 1))->toThrow(HookNotDeclared::class)
        ->and(app(HookRegistry::class)->get('vani.service.checkout.totals')->onError)->toBe('fail')
        ->and(app(HookRegistry::class)->get('vani.service.catalog.search')->onError)->toBe('skip');
});

it('vani.service.checkout.totals: plugin giảm giá dòng → quote và đơn đặt cùng tổng, Adjustment lưu vào đơn', function () {
    $before = ($this->quote)()->json('data.total.amount');
    discountEveryLine(50_000);

    $quote = ($this->quote)();
    expect($quote->json('data.total.amount'))->toBe($before - 50_000)
        ->and($quote->json('data.adjustments.0.type'))->toBe('loyalty');

    $this->postJson("/api/storefront/v1/checkout/{$this->cartId}/orders", C::orderPayload(['expected_total' => $before - 50_000]), [...$this->headers, 'Idempotency-Key' => 'service-hook-1'])->assertCreated();

    $order = Order::query()->with('adjustments')->sole();
    expect($order->total_amount)->toBe($before - 50_000)
        ->and($order->adjustments->pluck('type')->all())->toContain('loyalty');
});

it('vani.service.checkout.totals chạy trước guard: giảm giá vượt tổng dòng bị cắt về 0đ', function () {
    $quote = ($this->quote)()->json('data');
    discountEveryLine(10_000_000);

    $after = ($this->quote)()->json('data');
    expect($after['lines'][0]['total']['amount'])->toBe(0)
        ->and($after['total']['amount'])->toBe($quote['shipping_fee']['amount']);
});

it('vani.service.pricing.prices: giá plugin đổi được dùng ở giỏ và checkout', function () {
    app(HookManager::class)->onFilter('vani.service.pricing.prices', fn (array $prices): array => array_map(
        fn (ResolvedPrice $price): ResolvedPrice => new ResolvedPrice($price->variantId, Money::vnd(250_000), $price->compareAt, $price->discountPercent, 'vani.cod'),
        $prices,
    ), 10, 'vani.cod');

    expect(($this->quote)()->json('data.lines.0.unit_price.amount'))->toBe(250_000);
});

it('vani.service.catalog.product_detail: listener lỗi bị bỏ (on_error skip), listener khác vẫn chạy', function () {
    app(HookManager::class)->onFilter('vani.service.catalog.product_detail', fn (array $product): array => [...$product, 'vani.cod' => ['badge' => 'Mới']], 10, 'vani.cod');
    app(HookManager::class)->onFilter('vani.service.catalog.product_detail', fn (array $product): array => throw new RuntimeException('plugin hỏng'), 20, 'vani.cod');

    $this->getJson("/api/storefront/v1/products/{$this->s->style->slug}")->assertOk();
    $detail = app(CatalogReader::class)->productDetail($this->s->style->slug, 'vi', now()->getTimestamp());

    expect($detail['vani.cod'])->toBe(['badge' => 'Mới']);
});
