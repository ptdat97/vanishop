<?php

declare(strict_types=1);

namespace Modules\Promotion\Application;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Promotion\Contracts\Data\AppliedPromotion;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\Data\PromotionResult;
use Modules\Promotion\Contracts\Data\VoucherRejection;
use Modules\Promotion\Contracts\PromotionEngine;
use Modules\Promotion\Contracts\PromotionUnavailable;
use Modules\Promotion\Domain\DiscountMath;
use Modules\Promotion\Domain\Stacking;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Promotion\Persistence\Models\Voucher;
use Modules\Shared\Domain\Money\Money;

/**
 * Flow: docs/03-domains/promotion.md §4. Đánh giá chỉ đọc; ghi nhận dùng UPDATE có điều kiện (§5).
 */
final class PromotionEvaluator implements PromotionEngine
{
    public function __construct(
        private readonly PromotionRegistry $registry,
        private readonly int $maxDiscountBasisPoints,
    ) {}

    public function evaluate(PromotionContext $context): PromotionResult
    {
        $brandIds = array_values(array_unique(array_map(fn ($line) => $line->brandId, $context->lines)));
        if ($brandIds === []) {
            return new PromotionResult([], array_map(fn (string $code) => new VoucherRejection($code, 'not_applicable'), $context->voucherCodes));
        }

        [$voucherByPromotion, $rejected] = $this->vouchers($context, $brandIds);

        /** @var Collection<int, Promotion> $candidates */
        $candidates = Promotion::query()
            ->with('rules')
            ->whereIn('brand_id', $brandIds)
            ->where('status', 'active')
            ->where(fn ($query) => $query->where('requires_voucher', false)->orWhereIn('id', array_keys($voucherByPromotion)))
            ->orderByDesc('priority')->orderBy('id')
            ->get()
            ->filter(fn (Promotion $promotion): bool => $promotion->isRunningAt($context->now) && ! $this->exhausted($promotion));

        $subtotals = [];
        $discounted = [];
        foreach ($context->lines as $line) {
            $subtotals[$line->key] = $line->subtotal;
            $discounted[$line->key] = Money::zero($context->currencyCode);
        }

        $applied = [];
        foreach ($candidates as $promotion) {
            if ($promotion->stacking === Stacking::Exclusive && $applied !== []) {
                continue;
            }

            $eligibility = $this->eligibility($promotion, $context);
            $action = $this->registry->action($promotion->action_type);
            if ($eligibility === null || $eligibility->isEmpty() || $action === null) {
                continue;
            }

            $remaining = [];
            foreach ($eligibility->keys as $key) {
                $remaining[$key] = $subtotals[$key]->subtract($discounted[$key]);
            }

            $proposed = $action->apply($remaining, $promotion->action_config, $context->currencyCode);
            $lineDiscounts = array_filter(
                DiscountMath::capToFloor(array_intersect_key($proposed, $remaining), $subtotals, $discounted, $this->maxDiscountBasisPoints),
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
            $applied[] = new AppliedPromotion($promotion->id, $voucher?->id, $voucher?->code, $promotion->name, $lineDiscounts, $total);

            if ($promotion->stacking === Stacking::Exclusive) {
                break;
            }
        }

        $appliedVoucherIds = array_filter(array_map(fn (AppliedPromotion $promotion): ?int => $promotion->voucherId, $applied));
        foreach ($voucherByPromotion as $voucher) {
            if (! in_array($voucher->id, $appliedVoucherIds, true)) {
                $rejected[] = new VoucherRejection($voucher->code, 'not_applicable');
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

            DB::table('promotion_usages')->insert([
                'promotion_id' => $applied->promotionId, 'voucher_id' => $applied->voucherId, 'order_id' => $orderId, 'customer_id' => $customerId,
                'discount_amount' => $applied->total->amount, 'currency_code' => $currencyCode, 'status' => 'applied', 'created_at' => now(), 'updated_at' => now(),
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
            }
        });
    }

    /**
     * @param  list<int>  $brandIds
     * @return array{0: array<int, Voucher>, 1: list<VoucherRejection>}
     */
    private function vouchers(PromotionContext $context, array $brandIds): array
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
            $promotion = $voucher?->promotion; // BelongsToBrand: promotion ngoài phạm vi kênh → null

            $reason = match (true) {
                $voucher === null, $promotion === null, ! in_array($promotion->brand_id, $brandIds, true) => 'not_found',
                $voucher->status !== 'active', ! $promotion->isRunningAt($context->now) => 'not_active',
                $voucher->expires_at !== null && $voucher->expires_at->getTimestamp() <= $context->now => 'expired',
                ($voucher->usage_limit !== null && $voucher->used_count >= $voucher->usage_limit) || $this->exhausted($promotion) => 'exhausted',
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
     * Dòng của brand khuyến mãi, lọc qua mọi rule. null = có rule thuộc plugin không còn bật (bỏ qua khuyến mãi).
     */
    private function eligibility(Promotion $promotion, PromotionContext $context): ?Eligibility
    {
        $eligibility = new Eligibility(array_values(array_map(
            fn ($line): int => $line->key,
            array_filter($context->lines, fn ($line): bool => $line->brandId === $promotion->brand_id),
        )));

        foreach ($promotion->rules as $record) {
            $rule = $this->registry->rule($record->rule_type);
            if ($rule === null) {
                Log::warning('Khuyến mãi dùng rule chưa đăng ký (plugin tắt?) — bỏ qua.', ['promotion_id' => $promotion->id, 'rule_type' => $record->rule_type]);

                return null;
            }
            $eligibility = $eligibility->intersect($rule->evaluate($context, $record->config, $eligibility));
            if ($eligibility->isEmpty()) {
                return $eligibility;
            }
        }

        return $eligibility;
    }
}
