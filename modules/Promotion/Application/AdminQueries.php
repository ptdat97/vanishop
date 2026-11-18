<?php

declare(strict_types=1);

namespace Modules\Promotion\Application;

use Illuminate\Database\Eloquent\Collection;
use Modules\Promotion\Persistence\Models\Campaign;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Shared\Http\ListSort;

/**
 * Truy vấn đọc cho màn hình Admin của Promotion — controller không tự truy vấn (D4, R9).
 */
final class AdminQueries
{
    /**
     * @param  list<int>|null  $ids  id do bộ lọc plugin trả; null = không lọc
     * @return Collection<int, Promotion>
     */
    public function promotions(?array $ids, ?ListSort $sort = null): Collection
    {
        return Promotion::query()->withCount('vouchers')->when($ids !== null, fn ($query) => $query->whereIn('id', $ids))
            ->when($sort !== null, fn ($query) => $sort->apply($query), fn ($query) => $query->orderByDesc('priority')->orderByDesc('id'))->get();
    }

    /**
     * @return Collection<int, Campaign>
     */
    public function campaigns(): Collection
    {
        return Campaign::query()->withCount('promotions')->orderByDesc('starts_at')->get();
    }

    /**
     * Khuyến mãi chọn được cho chiến dịch: chưa thuộc chiến dịch nào hoặc thuộc chính chiến dịch đang sửa.
     *
     * @return Collection<int, Promotion>
     */
    public function promotionsForCampaign(?int $campaignId): Collection
    {
        return Promotion::query()->where(fn ($query) => $query->whereNull('campaign_id')->when($campaignId !== null, fn ($q) => $q->orWhere('campaign_id', $campaignId)))
            ->orderByDesc('id')->get(['id', 'name', 'requires_voucher']);
    }
}
