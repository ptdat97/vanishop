<?php

use Illuminate\Support\Facades\DB;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Promotion\Application\PromotionRegistry;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionLine;
use Modules\Promotion\Contracts\PromotionEngine;
use Modules\Promotion\Contracts\PromotionRule;
use Modules\Promotion\Contracts\PromotionUnavailable;
use Modules\Promotion\Persistence\Models\PromotionRuleRecord;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    ['brand' => $this->brand, 'channel' => $this->channel, 's' => $this->s, 'm' => $this->m] = C::store();
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), $this->channel->id, [$this->brand->id], 'vi'));
    $this->engine = app(PromotionEngine::class);
    $this->context = fn (array $codes = [], ?Brand $brand = null) => new PromotionContext($this->channel->id, null, 'VND', [
        new PromotionLine($this->s->id, ($brand ?? $this->brand)->id, $this->s->style_id, 1, Money::vnd(300_000), Money::vnd(300_000)),
        new PromotionLine($this->m->id, ($brand ?? $this->brand)->id, $this->m->style_id, 2, Money::vnd(200_000), Money::vnd(400_000)),
    ], $codes, now()->getTimestamp());
});

it('áp khuyến mãi tự động và voucher, cộng dồn theo priority trên số tiền còn lại', function () {
    C::promotion($this->brand, ['name' => 'Tự động 10%', 'requires_voucher' => false, 'priority' => 10]);
    C::promotion($this->brand, ['name' => 'Voucher 50k', 'action_type' => 'amount_off', 'action_config' => ['amount' => 70_000]], ['GIAM50' => 5]);

    $result = $this->engine->evaluate(($this->context)(['giam50 ']));

    expect(collect($result->applied)->pluck('name')->all())->toBe(['Tự động 10%', 'Voucher 50k'])
        ->and($result->applied[0]->total->amount)->toBe(70_000)
        ->and($result->applied[1]->voucherCode)->toBe('GIAM50')
        ->and($result->applied[1]->total->amount)->toBe(70_000)
        ->and($result->discountByLine())->toBe([$this->s->id => 30_000 + 30_000, $this->m->id => 40_000 + 40_000])
        ->and($result->rejectedVouchers)->toBe([]);
});

it('độc quyền: priority cao nhất thắng và dừng; bị bỏ qua nếu đã có khuyến mãi khác', function () {
    C::promotion($this->brand, ['name' => 'Độc quyền 30%', 'stacking' => 'exclusive', 'priority' => 20, 'requires_voucher' => false, 'action_config' => ['basis_points' => 3000]]);
    C::promotion($this->brand, ['name' => 'Cộng dồn 10%', 'priority' => 10, 'requires_voucher' => false]);

    expect(collect($this->engine->evaluate(($this->context)())->applied)->pluck('name')->all())->toBe(['Độc quyền 30%']);

    DB::table('promotions')->where('name', 'Độc quyền 30%')->update(['priority' => 5]);
    expect(collect($this->engine->evaluate(($this->context)())->applied)->pluck('name')->all())->toBe(['Cộng dồn 10%']);
});

it('không giảm quá giá sàn 50% mỗi dòng', function () {
    C::promotion($this->brand, ['name' => '40%', 'requires_voucher' => false, 'priority' => 2, 'action_config' => ['basis_points' => 4000]]);
    C::promotion($this->brand, ['name' => '30%', 'requires_voucher' => false, 'priority' => 1, 'action_config' => ['basis_points' => 3000]]);

    expect($this->engine->evaluate(($this->context)())->discountByLine())->toBe([$this->s->id => 150_000, $this->m->id => 200_000]);
});

it('báo lý do voucher không áp được', function () {
    C::promotion($this->brand, [], ['HETLUOT' => 1]);
    DB::table('vouchers')->where('code', 'HETLUOT')->update(['used_count' => 1]);
    C::promotion($this->brand, ['ends_at' => now()->subDay()], ['HETHAN' => null]);
    $other = Brand::factory()->create();
    C::promotion($other, [], ['BRANDKHAC' => null]);

    $reasons = collect($this->engine->evaluate(($this->context)(['KHONGCO', 'HETLUOT', 'HETHAN', 'BRANDKHAC']))->rejectedVouchers)->pluck('reason', 'code')->all();

    expect($reasons)->toBe(['KHONGCO' => 'not_found', 'HETLUOT' => 'exhausted', 'HETHAN' => 'not_active', 'BRANDKHAC' => 'not_found']);
});

it('rule của plugin lọc dòng đủ điều kiện; rule chưa đăng ký thì bỏ qua khuyến mãi', function () {
    $promotion = C::promotion($this->brand, ['requires_voucher' => false]);
    PromotionRuleRecord::query()->create(['promotion_id' => $promotion->id, 'rule_type' => 'only_variant', 'config' => ['variant_id' => $this->m->id]]);

    expect($this->engine->evaluate(($this->context)())->applied)->toBe([]);

    app()->instance('test.only_variant', new class implements PromotionRule
    {
        public function type(): string
        {
            return 'only_variant';
        }

        public function label(): string
        {
            return 'Chỉ một variant';
        }

        public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
        {
            return new Eligibility(array_values(array_filter($candidates->keys, fn (int $key) => $key === $config['variant_id'])));
        }
    });
    app()->tag(['test.only_variant'], PromotionRegistry::RULES_TAG);

    expect(app(PromotionEngine::class)->evaluate(($this->context)())->discountByLine())->toBe([$this->m->id => 40_000]);
});

it('ghi nhận sử dụng có điều kiện: hết lượt thì ném lỗi; hoàn lượt idempotent', function () {
    $promotion = C::promotion($this->brand, ['usage_limit' => 5], ['MOTLAN' => 1]);
    $result = $this->engine->evaluate(($this->context)(['MOTLAN']));

    DB::transaction(fn () => $this->engine->recordUsage(1001, null, 'VND', $result));
    expect(fn () => DB::transaction(fn () => $this->engine->recordUsage(1002, null, 'VND', $result)))
        ->toThrow(PromotionUnavailable::class);

    expect(DB::table('vouchers')->where('code', 'MOTLAN')->value('used_count'))->toEqual(1)
        ->and($promotion->fresh()->usage_count)->toBe(1)
        ->and($promotion->fresh()->budget_used_amount)->toBe(70_000);

    $this->engine->revertUsage(1001);
    $this->engine->revertUsage(1001);

    expect(DB::table('vouchers')->where('code', 'MOTLAN')->value('used_count'))->toEqual(0)
        ->and($promotion->fresh()->usage_count)->toBe(0)
        ->and($promotion->fresh()->budget_used_amount)->toBe(0)
        ->and(DB::table('promotion_usages')->where('order_id', 1001)->value('status'))->toBe('reverted');
});
