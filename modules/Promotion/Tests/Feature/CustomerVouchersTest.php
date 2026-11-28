<?php

use Illuminate\Support\Facades\DB;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Contracts\Extensions;
use Modules\Promotion\Contracts\Data\ManagedPromotion;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionLine;
use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Promotion\Contracts\PromotionEngine;
use Modules\Promotion\Contracts\PromotionRule;
use Modules\Promotion\Contracts\PromotionUnavailable;
use Modules\Promotion\Contracts\Vouchers;
use Modules\Promotion\Persistence\Models\Voucher;
use Modules\Promotion\Tests\Fixtures\MinSubtotalFixtureRule;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Domain\Money\Money;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
 * Lượt dùng mỗi khách, voucher gán khách, giảm tối đa, trần theo giá gốc, gợi ý "mua thêm" (Core 0.5.109).
 */
beforeEach(function () {
    ['brand' => $this->brand, 's' => $this->s, 'm' => $this->m] = C::store();
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));
    $this->engine = app(PromotionEngine::class);
    // Dòng S: 300.000đ × 1; dòng M: 200.000đ × 2. `compareAt` = giá gốc một đơn vị khi dòng đang giảm bằng bảng giá.
    $this->context = fn (array $codes = [], ?int $customerId = null, ?int $compareAtS = null) => new PromotionContext($customerId, 'VND', [
        new PromotionLine($this->s->id, $this->brand->id, $this->s->product_id, 1, Money::vnd(300_000), Money::vnd(300_000), $this->s->id,
            $compareAtS === null ? null : Money::vnd($compareAtS), $compareAtS === null ? null : 'flash'),
        new PromotionLine($this->m->id, $this->brand->id, $this->m->product_id, 2, Money::vnd(200_000), Money::vnd(400_000), $this->m->id),
    ], $codes, now()->getTimestamp());
});

it('lượt mỗi khách: khách đã dùng hết thì mã bị từ chối customer_limit, ghi nhận lần nữa thì ném lỗi; huỷ đơn trả lượt', function () {
    $promotion = C::promotion(['usage_limit_per_customer' => 1], ['MOTKHACH' => null]);
    $first = $this->engine->evaluate(($this->context)(['MOTKHACH'], 7));
    DB::transaction(fn () => $this->engine->recordUsage(2001, 7, 'VND', $first));

    $again = $this->engine->evaluate(($this->context)(['MOTKHACH'], 7));
    expect($again->applied)->toBe([])
        ->and($again->rejectedVouchers[0]->reason)->toBe('customer_limit')
        ->and($this->engine->evaluate(($this->context)(['MOTKHACH'], 8))->applied)->toHaveCount(1)
        // Khách vãng lai ở giỏ chưa biết là ai: chưa chặn (chặn lúc đặt đơn khi Checkout đã giải khách theo SĐT).
        ->and($this->engine->evaluate(($this->context)(['MOTKHACH']))->applied)->toHaveCount(1);

    // Hai đơn đặt song song cùng thấy mã dùng được: đơn thứ hai bị chặn trong transaction, lượt tổng không tăng thêm.
    expect(fn () => DB::transaction(fn () => $this->engine->recordUsage(2002, 7, 'VND', $first)))
        ->toThrow(PromotionUnavailable::class, 'Bạn đã dùng hết lượt');
    expect($promotion->fresh()->usage_count)->toBe(1);

    $this->engine->revertUsage(2001);
    $this->engine->revertUsage(2001);

    expect(DB::table('promotion_customer_usages')->where(['promotion_id' => $promotion->id, 'customer_id' => 7])->value('used_count'))->toEqual(0)
        ->and($this->engine->evaluate(($this->context)(['MOTKHACH'], 7))->applied)->toHaveCount(1);
});

it('voucher gán khách: chỉ đúng khách dùng được, khách khác và khách chưa đăng nhập nhận not_owner', function () {
    $promotion = C::promotion([], ['CHUNG' => null]);
    app(Vouchers::class)->create($promotion->id, 'rieng-7', 1, null, 7);

    $reasons = fn (?int $customerId) => collect($this->engine->evaluate(($this->context)(['RIENG-7'], $customerId))->rejectedVouchers)->pluck('reason')->all();

    expect($reasons(7))->toBe([])
        ->and($reasons(8))->toBe(['not_owner'])
        ->and($reasons(null))->toBe(['not_owner']);
});

it('Vouchers: sinh mã riêng có hạn cho khách, đọc theo khách; xoá mã chưa dùng, mã đã dùng chỉ tắt', function () {
    $promotion = C::promotion([], ['CHUNG' => null]);
    $vouchers = app(Vouchers::class);
    $expiresAt = now()->addDays(30)->startOfMinute()->getTimestamp();

    $codes = $vouchers->generate($promotion->id, 'vip-', 2, 1, $expiresAt, 7);
    $mine = $vouchers->forCustomer(7);

    expect($codes)->toHaveCount(2)
        ->and($codes[0])->toStartWith('VIP-')
        ->and(collect($mine)->pluck('code')->sort()->values()->all())->toBe(collect($codes)->sort()->values()->all())
        ->and($mine[0]->expiresAt)->toBe($expiresAt)
        ->and($mine[0]->usageLimit)->toBe(1)
        ->and($vouchers->ofPromotion($promotion->id))->toHaveCount(3);

    $used = $this->engine->evaluate(($this->context)([$codes[0]], 7));
    DB::transaction(fn () => $this->engine->recordUsage(2101, 7, 'VND', $used));
    $usedId = (int) Voucher::query()->where('code', $codes[0])->value('id');
    $unusedId = (int) Voucher::query()->where('code', $codes[1])->value('id');
    $vouchers->remove($usedId);
    $vouchers->remove($unusedId);

    expect(Voucher::query()->find($usedId)?->status)->toBe('inactive')
        ->and(Voucher::query()->find($unusedId))->toBeNull();
});

it('ManagedPromotions: khuyến mãi cần mã + lượt mỗi khách; usage() trả lượt và tổng giảm theo khoá của plugin', function () {
    $managed = app(ManagedPromotions::class);
    $id = $managed->sync('vani.coupon-code', '12', new ManagedPromotion('Mã 10%', true, 'percent_off', ['basis_points' => 1000],
        requiresVoucher: true, usageLimitPerCustomer: 2));
    app(Vouchers::class)->create($id, 'GIAM10');

    expect($this->engine->evaluate(($this->context)())->applied)->toBe([]);

    DB::transaction(fn () => $this->engine->recordUsage(2201, 7, 'VND', $this->engine->evaluate(($this->context)(['GIAM10'], 7))));
    $usage = $managed->usage('vani.coupon-code')[12];

    expect([$usage->promotionId, $usage->usageCount, $usage->discountAmount])->toBe([$id, 1, 70_000])
        ->and(DB::table('promotions')->where('id', $id)->value('usage_limit_per_customer'))->toEqual(2);
});

it('giảm % có mức tối đa: tổng giảm của khuyến mãi bằng mức tối đa, chia theo tỷ trọng dòng', function () {
    C::promotion(['requires_voucher' => false, 'action_config' => ['basis_points' => 2000, 'max_amount' => 70_000]]);

    // 20% = 60.000 + 80.000 = 140.000 → kẹp 70.000, chia 3 : 4.
    expect($this->engine->evaluate(($this->context)())->discountByLine())->toBe([$this->s->id => 30_000, $this->m->id => 40_000]);
});

it('CustomerMerged: chuyển voucher riêng và cộng lượt đã dùng mỗi khách sang hồ sơ được giữ (L75)', function () {
    $promotion = C::promotion(['usage_limit_per_customer' => 2], ['CHUNG' => null]);
    $vouchers = app(Vouchers::class);
    $vouchers->create($promotion->id, 'RIENG-SRC', 1, null, 101);

    // Hồ sơ nguồn dùng 1 lượt, hồ sơ đích dùng 1 lượt → sau gộp tổng lượt là 2 (chạm trần 2).
    DB::transaction(fn () => $this->engine->recordUsage(3001, 101, 'VND', $this->engine->evaluate(($this->context)(['CHUNG'], 101))));
    DB::transaction(fn () => $this->engine->recordUsage(3002, 202, 'VND', $this->engine->evaluate(($this->context)(['CHUNG'], 202))));

    event(new \Modules\Customer\Events\CustomerMerged(101, 202, 1));

    expect($vouchers->forCustomer(101))->toBe([])
        ->and(collect($vouchers->forCustomer(202))->pluck('code')->all())->toBe(['RIENG-SRC'])
        ->and(DB::table('promotion_customer_usages')->where(['promotion_id' => $promotion->id, 'customer_id' => 202])->value('used_count'))->toEqual(2)
        ->and(DB::table('promotion_customer_usages')->where('customer_id', 101)->exists())->toBeFalse()
        ->and($this->engine->evaluate(($this->context)(['CHUNG'], 202))->rejectedVouchers[0]->reason)->toBe('customer_limit');
});

it('trần giảm tính theo giá gốc: dòng đã giảm 50% bằng bảng giá không được giảm thêm, dòng giảm ít hơn chỉ giảm tới trần', function () {
    C::promotion(['requires_voucher' => false, 'action_config' => ['basis_points' => 4000]]);

    // Giá gốc 600.000 → 300.000 đã là −50%: không còn chỗ.
    expect($this->engine->evaluate(($this->context)(compareAtS: 600_000))->discountByLine())->toBe([$this->m->id => 160_000]);
    // Giá gốc 400.000: trần 200.000 − đã giảm 100.000 = còn 100.000 (40% đề xuất 120.000).
    expect($this->engine->evaluate(($this->context)(compareAtS: 400_000))->discountByLine())->toBe([$this->s->id => 100_000, $this->m->id => 160_000]);
});

it('voucher chưa đạt rule có giải thích: lý do not_applicable kèm câu gợi ý và số tiền còn thiếu', function () {
    app(Extensions::class)->tag([MinSubtotalFixtureRule::class], PromotionRule::TAG);
    $promotion = C::promotion([], ['DON1TR' => null]);
    $promotion->rules()->create(['rule_type' => 'fixture_min_subtotal', 'config' => ['min' => 1_000_000]]);

    $rejection = $this->engine->evaluate(($this->context)(['DON1TR']))->rejectedVouchers[0];

    expect([$rejection->reason, $rejection->hint, $rejection->shortfall?->amount])->toBe(['not_applicable', 'Mua thêm 300000đ để dùng mã', 300_000]);
});
