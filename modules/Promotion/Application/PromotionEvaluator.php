<?php

declare(strict_types=1);

namespace Modules\Promotion\Application;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Promotion\Contracts\CartContentRule;
use Modules\Promotion\Contracts\ContextualPromotionAction;
use Modules\Promotion\Contracts\Data\AppliedPromotion;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\Ineligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionResult;
use Modules\Promotion\Contracts\Data\VoucherRejection;
use Modules\Promotion\Contracts\ExplainsIneligibility;
use Modules\Promotion\Contracts\PromotionAction;
use Modules\Promotion\Contracts\PromotionEngine;
use Modules\Promotion\Contracts\PromotionUnavailable;
use Modules\Promotion\Domain\DiscountMath;
use Modules\Promotion\Domain\Stacking;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Promotion\Persistence\Models\PromotionRuleRecord;
use Modules\Promotion\Persistence\Models\Voucher;
use Modules\Shared\Domain\Money\Money;

/**
 * Flow: docs/03-domains/promotion.md §4. Đánh giá chỉ đọc; ghi nhận dùng UPDATE có điều kiện (§5).
 *
 * @phpstan-import-type RecheckConfig from AppliedPromotion
 */
final class PromotionEvaluator implements PromotionEngine
{
    /** Trần 100% giá dòng — không giới hạn ngoài tổng không âm (mặc định trung lập của Core, ADR-029). */
    public const NO_CAP_BP = 10_000;

    public function __construct(
        private readonly PromotionRegistry $registry,
        private readonly int $maxDiscountBasisPoints,
    ) {}

    public function evaluate(PromotionContext $context): PromotionResult
    {
        // Không có filter trên kết quả: khuyến mãi mở rộng qua PromotionRule/PromotionAction (gồm ContextualPromotionAction)
        // để mức giảm luôn đi qua trần giá sàn và ngân sách của Core.
        return $this->evaluateRules($context);
    }

    private function evaluateRules(PromotionContext $context): PromotionResult
    {
        if ($context->lines === []) {
            return new PromotionResult([], array_map(fn (string $code) => new VoucherRejection($code, 'not_applicable'), $context->voucherCodes));
        }

        $customerUsed = $this->customerUsage($context->customerId);
        [$voucherByPromotion, $rejected] = $this->vouchers($context, $customerUsed);

        /** @var Collection<int, Promotion> $candidates */
        $candidates = Promotion::query()
            ->with('rules')
            ->where('status', 'active')
            ->where(fn ($query) => $query->where('requires_voucher', false)->orWhereIn('id', array_keys($voucherByPromotion)))
            ->orderByDesc('priority')->orderBy('id')
            ->get()
            ->filter(fn (Promotion $promotion): bool => $promotion->isRunningAt($context->now) && ! $this->exhausted($promotion)
                && ! $this->customerExhausted($promotion, $context->customerId, $customerUsed));

        [$subtotals, $references, $discounted] = $this->lineAmounts($context);

        $applied = [];
        /** @var array<int, array{type: string, config: array<string, mixed>, candidates: Eligibility}> $failures */
        $failures = [];
        foreach ($candidates as $promotion) {
            if ($promotion->stacking === Stacking::Exclusive && $applied !== []) {
                continue;
            }

            $config = $this->configuration($promotion);
            $failure = null;
            $eligibility = $this->eligibility($promotion->id, $config['rules'], $context, failure: $failure);
            if ($failure !== null) {
                $failures[$promotion->id] = $failure;
            }
            $action = $this->registry->action($config['action_type']);
            if ($eligibility === null || $eligibility->isEmpty() || $action === null) {
                continue;
            }

            $remaining = [];
            foreach ($eligibility->keys as $key) {
                $remaining[$key] = $subtotals[$key]->subtract($discounted[$key]);
            }

            $proposed = $this->propose($action, $context, $remaining, $config['action_config']);
            $lineDiscounts = array_filter(
                DiscountMath::capToFloor(array_intersect_key($proposed, $remaining), $subtotals, $discounted, $this->maxDiscountBasisPoints, $references),
                fn (Money $discount): bool => $discount->isPositive(),
            );
            $total = Money::of(array_sum(array_map(fn (Money $money): int => $money->amount, $lineDiscounts)), $context->currencyCode);

            if ($total->isZero() || ($promotion->budget_amount !== null && $promotion->budget_used_amount + $total->amount > $promotion->budget_amount)) {
                continue;
            }

            foreach ($lineDiscounts as $key => $discount) {
                $discounted[$key] = $discounted[$key]->add($discount);
            }

            $voucher = $voucherByPromotion[$promotion->id] ?? null;
            $applied[] = new AppliedPromotion(
                $promotion->id, $voucher?->id, $voucher?->code, $promotion->name, $lineDiscounts, $total,
                $action instanceof ContextualPromotionAction ? $config : null,
            );

            if ($promotion->stacking === Stacking::Exclusive) {
                break;
            }
        }

        $appliedVoucherIds = array_filter(array_map(fn (AppliedPromotion $promotion): ?int => $promotion->voucherId, $applied));
        foreach ($voucherByPromotion as $voucher) {
            if (! in_array($voucher->id, $appliedVoucherIds, true)) {
                $explained = isset($failures[$voucher->promotion_id]) ? $this->explain($context, $failures[$voucher->promotion_id]) : null;
                $rejected[] = new VoucherRejection($voucher->code, 'not_applicable', $explained?->message, $explained?->shortfall);
            }
        }

        return new PromotionResult($applied, $rejected);
    }

    public function recordUsage(int $orderId, ?int $customerId, string $currencyCode, PromotionResult $result): void
    {
        foreach ($result->applied as $applied) {
            if ($applied->voucherId !== null) {
                $updated = DB::table('vouchers')->where('id', $applied->voucherId)
                    ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('used_count', '<', 'usage_limit'))
                    ->increment('used_count');
                if ($updated === 0) {
                    throw PromotionUnavailable::voucherExhausted((string) $applied->voucherCode);
                }
            }

            $updated = DB::table('promotions')->where('id', $applied->promotionId)
                ->where(fn ($query) => $query->whereNull('usage_limit')->orWhereColumn('usage_count', '<', 'usage_limit'))
                ->where(fn ($query) => $query->whereNull('budget_amount')->orWhereRaw('budget_used_amount + ? <= budget_amount', [$applied->total->amount]))
                ->update([
                    'usage_count' => DB::raw('usage_count + 1'),
                    'budget_used_amount' => DB::raw('budget_used_amount + '.(int) $applied->total->amount),
                    'updated_at' => now(),
                ]);
            if ($updated === 0) {
                throw PromotionUnavailable::limitReached($applied->name);
            }
            if ($customerId !== null) {
                $this->countCustomerUsage($applied, $customerId);
            }

            DB::table('promotion_usages')->insert([
                'promotion_id' => $applied->promotionId, 'voucher_id' => $applied->voucherId, 'order_id' => $orderId, 'customer_id' => $customerId,
                'discount_amount' => $applied->total->amount, 'currency_code' => $currencyCode, 'status' => 'applied', 'created_at' => now(), 'updated_at' => now(),
                'recheck_config' => $applied->recheckConfig === null ? null : json_encode($applied->recheckConfig, JSON_THROW_ON_ERROR),
            ]);
        }
    }

    public function revertUsage(int $orderId): void
    {
        DB::transaction(function () use ($orderId): void {
            $usages = DB::table('promotion_usages')->where('order_id', $orderId)->where('status', 'applied')->lockForUpdate()->get();
            foreach ($usages as $usage) {
                DB::table('promotion_usages')->where('id', $usage->id)->update(['status' => 'reverted', 'updated_at' => now()]);
                DB::table('promotions')->where('id', $usage->promotion_id)->update([
                    'usage_count' => DB::raw('CASE WHEN usage_count > 0 THEN usage_count - 1 ELSE 0 END'),
                    'budget_used_amount' => DB::raw('CASE WHEN budget_used_amount > '.(int) $usage->discount_amount.' THEN budget_used_amount - '.(int) $usage->discount_amount.' ELSE 0 END'),
                    'updated_at' => now(),
                ]);
                if ($usage->voucher_id !== null) {
                    DB::table('vouchers')->where('id', $usage->voucher_id)->where('used_count', '>', 0)->decrement('used_count');
                }
                if ($usage->customer_id !== null) {
                    DB::table('promotion_customer_usages')->where('promotion_id', $usage->promotion_id)->where('customer_id', $usage->customer_id)
                        ->where('used_count', '>', 0)->decrement('used_count', 1, ['updated_at' => now()]);
                }
            }
        });
    }

    public function recheck(PromotionContext $context, array $promotionIds, ?int $orderId = null): array
    {
        $configs = $orderId === null ? [] : DB::table('promotion_usages')->where('order_id', $orderId)
            ->whereIn('promotion_id', $promotionIds)->whereNotNull('recheck_config')->pluck('recheck_config', 'promotion_id')
            ->map(fn (string $config): array => json_decode($config, true, flags: JSON_THROW_ON_ERROR))->all();
        $missing = array_diff($promotionIds, array_keys($configs));
        if ($missing !== []) {
            foreach (Promotion::query()->with('rules')->whereKey($missing)->get() as $promotion) {
                $configs[$promotion->id] = $this->configuration($promotion);
            }
        }
        [$subtotals, $references, $discounted] = $this->lineAmounts($context);

        $result = [];
        foreach ($promotionIds as $promotionId) {
            $config = $configs[$promotionId] ?? null;
            $action = $config === null ? null : $this->registry->action($config['action_type']);
            $eligibility = $config === null ? null : $this->eligibility($promotionId, $config['rules'], $context, cartContentOnly: true);
            if ($config === null || $action === null || $eligibility === null) {
                continue; // không kiểm tra lại được → nơi gọi giữ nguyên
            }
            if ($eligibility->isEmpty() || $context->lines === []) {
                $result[$promotionId] = [];

                continue;
            }

            $remaining = [];
            foreach ($eligibility->keys as $key) {
                $remaining[$key] = $subtotals[$key]->subtract($discounted[$key]);
            }
            $proposed = $this->propose($action, $context, $remaining, $config['action_config']);
            $lineDiscounts = array_filter(
                DiscountMath::capToFloor(array_intersect_key($proposed, $remaining), $subtotals, $discounted, $this->maxDiscountBasisPoints, $references),
                fn (Money $discount): bool => $discount->isPositive(),
            );
            foreach ($lineDiscounts as $key => $discount) {
                $discounted[$key] = $discounted[$key]->add($discount);
            }
            $result[$promotionId] = $lineDiscounts;
        }

        return $result;
    }

    public function adjustUsage(int $orderId, array $currentDiscounts): void
    {
        $usages = DB::table('promotion_usages')->where('order_id', $orderId)->where('status', 'applied')->whereIn('promotion_id', array_keys($currentDiscounts))->lockForUpdate()->get();
        foreach ($usages as $usage) {
            $released = (int) $usage->discount_amount - max(0, (int) $currentDiscounts[$usage->promotion_id]);
            if ($released <= 0) {
                continue;
            }
            DB::table('promotion_usages')->where('id', $usage->id)->update(['discount_amount' => (int) $usage->discount_amount - $released, 'updated_at' => now()]);
            DB::table('promotions')->where('id', $usage->promotion_id)->update([
                'budget_used_amount' => DB::raw('CASE WHEN budget_used_amount > '.$released.' THEN budget_used_amount - '.$released.' ELSE 0 END'),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<int, int>  $customerUsed
     * @return array{0: array<int, Voucher>, 1: list<VoucherRejection>}
     */
    private function vouchers(PromotionContext $context, array $customerUsed): array
    {
        $codes = array_values(array_unique(array_filter(array_map(Voucher::normalize(...), $context->voucherCodes))));
        if ($codes === []) {
            return [[], []];
        }

        $vouchers = Voucher::query()->with('promotion')->whereIn('code', $codes)->get()->keyBy('code');
        $byPromotion = [];
        $rejected = [];

        foreach ($codes as $code) {
            $voucher = $vouchers->get($code);
            $promotion = $voucher?->promotion;

            $reason = match (true) {
                $voucher === null, $promotion === null => 'not_found',
                $voucher->status !== 'active', ! $promotion->isRunningAt($context->now) => 'not_active',
                $voucher->expires_at !== null && $voucher->expires_at->getTimestamp() <= $context->now => 'expired',
                $voucher->customer_id !== null && $voucher->customer_id !== $context->customerId => 'not_owner',
                ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) || $this->exhausted($promotion) => 'exhausted',
                $this->customerExhausted($promotion, $context->customerId, $customerUsed) => 'customer_limit',
                isset($byPromotion[$promotion->id]) => 'not_applicable', // một voucher cho mỗi khuyến mãi
                default => null,
            };

            if ($reason === null) {
                $byPromotion[$promotion->id] = $voucher;
            } else {
                $rejected[] = new VoucherRejection($code, $reason);
            }
        }

        return [$byPromotion, $rejected];
    }

    private function exhausted(Promotion $promotion): bool
    {
        return ($promotion->usage_limit !== null && $promotion->usage_count >= $promotion->usage_limit)
            || ($promotion->budget_amount !== null && $promotion->budget_used_amount >= $promotion->budget_amount);
    }

    /**
     * Lượt khách đã dùng theo khuyến mãi (đơn chưa huỷ). Khách chưa xác định (vãng lai ở giỏ) → rỗng: lượt mỗi khách được
     * chặn lúc đặt đơn, khi Checkout đã giải khách theo SĐT.
     *
     * @return array<int, int> promotion id => lượt
     */
    private function customerUsage(?int $customerId): array
    {
        return $customerId === null ? [] : DB::table('promotion_customer_usages')->where('customer_id', $customerId)->where('used_count', '>', 0)
            ->pluck('used_count', 'promotion_id')->map(fn (mixed $count): int => (int) $count)->all();
    }

    /**
     * @param  array<int, int>  $customerUsed
     */
    private function customerExhausted(Promotion $promotion, ?int $customerId, array $customerUsed): bool
    {
        return $customerId !== null && $promotion->usage_limit_per_customer !== null
            && ($customerUsed[$promotion->id] ?? 0) >= $promotion->usage_limit_per_customer;
    }

    /**
     * Đếm lượt của khách bằng UPDATE có điều kiện (trong transaction đặt hàng); hết lượt → PromotionUnavailable.
     * Đếm cả khuyến mãi không giới hạn mỗi khách, để đặt giới hạn về sau vẫn tính đúng lượt đã dùng.
     */
    private function countCustomerUsage(AppliedPromotion $applied, int $customerId): void
    {
        $limit = DB::table('promotions')->where('id', $applied->promotionId)->value('usage_limit_per_customer');
        DB::table('promotion_customer_usages')->insertOrIgnore([
            'promotion_id' => $applied->promotionId, 'customer_id' => $customerId, 'used_count' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $updated = DB::table('promotion_customer_usages')->where('promotion_id', $applied->promotionId)->where('customer_id', $customerId)
            ->when($limit !== null, fn ($query) => $query->where('used_count', '<', (int) $limit))
            ->increment('used_count', 1, ['updated_at' => now()]);
        if ($updated === 0) {
            throw PromotionUnavailable::customerLimitReached($applied->name);
        }
    }

    /**
     * Thành tiền theo giá bán, theo giá gốc (mốc trần giảm) và phần đã giảm (bắt đầu 0) của từng dòng.
     *
     * @return array{0: array<int, Money>, 1: array<int, Money>, 2: array<int, Money>}
     */
    private function lineAmounts(PromotionContext $context): array
    {
        $subtotals = [];
        $references = [];
        $discounted = [];
        foreach ($context->lines as $line) {
            $subtotals[$line->key] = $line->subtotal;
            $references[$line->key] = $line->referenceSubtotal();
            $discounted[$line->key] = Money::zero($context->currencyCode);
        }

        return [$subtotals, $references, $discounted];
    }

    /**
     * @param  array{type: string, config: array<string, mixed>, candidates: Eligibility}  $failure
     */
    private function explain(PromotionContext $context, array $failure): ?Ineligibility
    {
        $rule = $this->registry->rule($failure['type']);

        return $rule instanceof ExplainsIneligibility ? $rule->explain($context, $failure['config'], $failure['candidates']) : null;
    }

    /**
     * @param  array<int, Money>  $remaining
     * @param  array<string, mixed>  $config
     * @return array<int, Money>
     */
    private function propose(PromotionAction $action, PromotionContext $context, array $remaining, array $config): array
    {
        return $action instanceof ContextualPromotionAction
            ? $action->applyInContext($context, $remaining, $config)
            : $action->apply($remaining, $config, $context->currencyCode);
    }

    /** @return RecheckConfig */
    private function configuration(Promotion $promotion): array
    {
        return [
            'action_type' => $promotion->action_type,
            'action_config' => $promotion->action_config,
            'rules' => $promotion->rules->map(fn (PromotionRuleRecord $rule): array => ['type' => $rule->rule_type, 'config' => $rule->config])->values()->all(),
        ];
    }

    /**
     * Mọi dòng của giỏ, lọc qua mọi rule (hoặc chỉ `CartContentRule` khi kiểm tra lại). Rule làm tập rỗng được ghi vào
     * `$failure` (để giải thích cho khách).
     *
     * @param  list<array{type: string, config: array<string, mixed>}>  $rules
     *
     * @param-out array{type: string, config: array<string, mixed>, candidates: Eligibility}|null  $failure
     */
    private function eligibility(int $promotionId, array $rules, PromotionContext $context, bool $cartContentOnly = false, ?array &$failure = null): ?Eligibility
    {
        $failure = null;
        $eligibility = new Eligibility(array_map(fn ($line): int => $line->key, $context->lines));

        foreach ($rules as $record) {
            $rule = $this->registry->rule($record['type']);
            if ($cartContentOnly && $rule !== null && ! $rule instanceof CartContentRule) {
                continue;
            }
            if ($rule === null) {
                Log::warning('Khuyến mãi dùng rule chưa đăng ký (plugin tắt?) — bỏ qua.', ['promotion_id' => $promotionId, 'rule_type' => $record['type']]);

                return null;
            }
            $candidates = $eligibility;
            $eligibility = $eligibility->intersect($rule->evaluate($context, $record['config'], $eligibility));
            if ($eligibility->isEmpty()) {
                $failure = ['type' => $record['type'], 'config' => $record['config'], 'candidates' => $candidates];

                return $eligibility;
            }
        }

        return $eligibility;
    }
}
