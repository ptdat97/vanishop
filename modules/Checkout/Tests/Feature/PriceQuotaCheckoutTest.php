<?php

use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Pricing\Contracts\Data\ManagedPriceList;
use Modules\Pricing\Contracts\Data\PriceQuotaRule;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\Data\QuotaLine;
use Modules\Pricing\Contracts\ManagedPriceLists;
use Modules\Pricing\Contracts\PriceQuotaExceeded;
use Modules\Pricing\Contracts\PriceQuotas;
use Modules\Pricing\Contracts\PriceResolver;

require_once __DIR__.'/CheckoutTestHelpers.php';

/*
 * Suất giá (0.5.117): flash sale có suất tổng và giới hạn mỗi khách — trừ khi đặt đơn, hết suất thì giá về giá thường, vượt
 * giới hạn thì chặn kèm số tối đa, huỷ đơn hoàn suất.
 */
beforeEach(function () {
    ['s' => $this->s] = C::store(); // giá niêm yết S 300.000
    $this->api = '/api/storefront/v1';
    $this->flash = function (?int $limit, ?int $perCustomer): int {
        $listId = app(ManagedPriceLists::class)->sync('vani.pricing-campaign', '1', new ManagedPriceList(
            'Flash sale', ManagedPriceList::TYPE_SALE, true, [$this->s->id => 200_000], priority: 30,
            quotas: [new PriceQuotaRule(PriceQuotaRule::SCOPE_PRODUCT, $this->s->product_id, $limit, $perCustomer)],
        ));

        return $listId;
    };
    $this->order = function (int $quantity, string $phone, string $key) {
        $created = $this->postJson("{$this->api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("{$this->api}/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => $quantity], $headers)->assertOk();

        $payload = C::orderPayload(['contact' => ['phone' => $phone]]);
        $total = $this->postJson("{$this->api}/checkout/{$created->json('data.id')}/quote", $payload, $headers)->json('data.total.amount');

        return [$this->postJson("{$this->api}/checkout/{$created->json('data.id')}/orders", [...$payload, 'expected_total' => $total], [...$headers, 'Idempotency-Key' => $key]), $headers];
    };
    $this->price = fn (?int $customerId = null): int => app(PriceResolver::class)->forVariants([$this->s->id], new PricingContext(now()->getTimestamp(), customerId: $customerId))[$this->s->id]->amount->amount;
});

it('đặt đơn trừ suất; hết suất thì giá về giá thường cho mọi khách; huỷ đơn hoàn suất và giá sale trở lại', function () {
    $listId = ($this->flash)(2, null);

    [$first] = ($this->order)(2, '0912000001', 'quota-0001');
    $first->assertCreated()->assertJsonPath('data.total.amount', 400_000 + 30_000); // + phí giao dưới ngưỡng miễn phí
    expect(app(PriceQuotas::class)->status($listId)[0]->sold)->toBe(2)
        ->and(($this->price)())->toBe(300_000);

    // Khách sau mua theo giá thường (không bị chặn).
    [$second] = ($this->order)(1, '0912000002', 'quota-0002');
    $second->assertCreated()->assertJsonPath('data.total.amount', 300_000 + 30_000); // + phí giao dưới ngưỡng miễn phí

    $this->postJson("{$this->api}/orders/{$first->json('data.id')}/cancel", ['reason' => 'Đổi ý'], ['X-Vani-Order-Token' => $first->json('data.access_token')])->assertOk();
    expect(app(PriceQuotas::class)->status($listId)[0]->sold)->toBe(0)
        ->and(($this->price)())->toBe(200_000);
});

it('mỗi khách tối đa 2: đặt 3 bị chặn kèm số còn mua được; đã mua đủ thì lần sau khách đó thấy giá thường', function () {
    ($this->flash)(null, 2);

    [$tooMany] = ($this->order)(3, '0912000003', 'quota-0003');
    $tooMany->assertStatus(422)->assertJsonPath('error.details.issues.0.code', 'price_quota')
        ->assertJsonPath('error.details.issues.0.message', 'Mỗi khách tối đa 2 sản phẩm giá ưu đãi — bạn còn mua được 2.');

    [$ok] = ($this->order)(2, '0912000003', 'quota-0004');
    $ok->assertCreated();
    $customerId = (int) DB::table('orders')->where('public_id', $ok->json('data.id'))->value('customer_id');

    expect(($this->price)($customerId))->toBe(300_000)
        ->and(($this->price)())->toBe(200_000);
});

it('hai đơn cùng thấy còn suất: đơn ghi nhận sau bị từ chối trong transaction, không bán vượt suất', function () {
    $listId = ($this->flash)(1, null);
    $line = [new QuotaLine($this->s->id, $this->s->product_id, 1, app(ManagedPriceLists::class)->lists('vani.pricing-campaign')[1]['code'])];

    DB::transaction(fn () => app(PriceQuotas::class)->consume(9001, null, $line));
    expect(fn () => DB::transaction(fn () => app(PriceQuotas::class)->consume(9002, null, $line)))
        ->toThrow(PriceQuotaExceeded::class);
    expect(app(PriceQuotas::class)->status($listId)[0]->sold)->toBe(1);
});

it('huỷ một phần hoàn suất theo số lượng huỷ; huỷ cả đơn hoàn phần còn lại một lần', function () {
    $listId = ($this->flash)(5, null);
    $code = app(ManagedPriceLists::class)->lists('vani.pricing-campaign')[1]['code'];
    $quotas = app(PriceQuotas::class);
    DB::transaction(fn () => $quotas->consume(9101, 7, [new QuotaLine($this->s->id, $this->s->product_id, 3, $code)]));

    $quotas->release(9101, [$this->s->id => 1]);
    expect($quotas->status($listId)[0]->sold)->toBe(2);

    $quotas->revert(9101);
    $quotas->revert(9101);
    expect($quotas->status($listId)[0]->sold)->toBe(0)
        ->and(DB::table('price_quota_customers')->where('customer_id', 7)->value('used'))->toEqual(0);
});
