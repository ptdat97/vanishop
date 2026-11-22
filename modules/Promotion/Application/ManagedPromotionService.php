<?php

declare(strict_types=1);

namespace Modules\Promotion\Application;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Promotion\Contracts\Data\ManagedPromotion;
use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Promotion\Domain\Stacking;
use Modules\Promotion\Persistence\Models\Promotion;

/**
 * Khuyến mãi do plugin quản lý: đi qua cùng {@see PromotionService::save()} (validate rule/action, audit, lock version)
 * như Admin, chỉ thêm khoá (plugin, khoá của plugin).
 */
final class ManagedPromotionService implements ManagedPromotions
{
    public function __construct(private readonly PromotionService $promotions) {}

    public function sync(string $owner, string $key, ManagedPromotion $promotion): int
    {
        $existing = Promotion::query()->where('managed_by', $owner)->where('managed_key', $key)->first();
        $data = [
            'managed_by' => $owner,
            'managed_key' => $key,
            'managed_route' => $promotion->editRoute,
            'name' => $promotion->name,
            'status' => $promotion->active ? 'active' : 'inactive',
            'starts_at' => $promotion->startsAt === null ? null : Carbon::createFromTimestamp($promotion->startsAt),
            'ends_at' => $promotion->endsAt === null ? null : Carbon::createFromTimestamp($promotion->endsAt),
            'priority' => $promotion->priority,
            'stacking' => $promotion->exclusive ? Stacking::Exclusive : Stacking::Combinable,
            'requires_voucher' => false,
            'action_type' => $promotion->actionType,
            'action_config' => $promotion->actionConfig,
            'usage_limit' => $promotion->usageLimit,
            'budget_amount' => $promotion->budgetAmount,
            'rules' => $promotion->rules,
        ];

        return $this->promotions->save($data, $existing, $existing?->lock_version)->id;
    }

    public function remove(string $owner, string $key): void
    {
        $promotion = Promotion::query()->where('managed_by', $owner)->where('managed_key', $key)->first();
        if ($promotion === null) {
            return;
        }
        if (DB::table('promotion_usages')->where('promotion_id', $promotion->id)->exists()) {
            $promotion->update(['status' => 'inactive', 'managed_route' => null]);

            return;
        }
        $this->promotions->delete($promotion);
    }

    public function ids(string $owner): array
    {
        return Promotion::query()->where('managed_by', $owner)->pluck('id', 'managed_key')->map(fn ($id): int => (int) $id)->all();
    }
}
