<?php

declare(strict_types=1);

namespace Modules\Promotion\Application;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Ordering\Contracts\Data\SalesTotals;
use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Pricing\Contracts\PriceListSchedule;
use Modules\Promotion\Persistence\Models\Campaign;
use Modules\Promotion\Persistence\Models\Promotion;

/**
 * Campaign (roadmap Phase 8): gói khuyến mãi + bảng giá sale/member chạy chung lịch. Campaign SỞ HỮU lịch: khi kích hoạt,
 * sửa lịch, thêm thành viên hay dừng, lịch + trạng thái được ghi xuống từng thành viên (khuyến mãi trực tiếp, bảng giá
 * qua PriceListSchedule) — thành viên tự kiểm tra khung giờ khi tính giá/khuyến mãi, không cần job định kỳ.
 *
 * Nháp: thành viên tắt. Đang chạy (active): thành viên bật với khung giờ của campaign. Dừng (stopped, dừng khẩn cấp):
 * mọi thành viên tắt ngay, không bật lại được (tạo campaign mới).
 */
final class CampaignService
{
    public function __construct(
        private readonly PriceListSchedule $priceLists,
        private readonly OrderStatistics $statistics,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{code: string, name: string, description: ?string, starts_at: string, ends_at: string}  $data
     * @param  list<int>  $promotionIds
     * @param  list<int>|null  $priceListIds  null = giữ nguyên (người sửa không có quyền bảng giá)
     */
    public function save(array $data, array $promotionIds, ?array $priceListIds, ?Campaign $campaign = null, ?int $expectedLockVersion = null): Campaign
    {
        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt = Carbon::parse($data['ends_at']);
        if ($endsAt->lte($startsAt)) {
            throw ValidationException::withMessages(['ends_at' => __('promotion::messages.campaign_window_invalid')]);
        }
        if ($campaign?->status === Campaign::STOPPED) {
            throw ValidationException::withMessages(['campaign' => __('promotion::messages.campaign_stopped')]);
        }

        return DB::transaction(function () use ($data, $startsAt, $endsAt, $promotionIds, $priceListIds, $campaign, $expectedLockVersion): Campaign {
            if ($campaign !== null && Campaign::query()->whereKey($campaign->id)->where('lock_version', $expectedLockVersion)->increment('lock_version') === 0) {
                throw ValidationException::withMessages(['lock_version' => __('promotion::messages.stale')]);
            }
            $campaign ??= new Campaign(['status' => Campaign::DRAFT]);
            $campaign->fill(['code' => $data['code'], 'name' => $data['name'], 'description' => $data['description'], 'starts_at' => $startsAt, 'ends_at' => $endsAt])->save();
            $campaign->refresh();

            $this->syncPromotions($campaign, $promotionIds);
            if ($priceListIds !== null) {
                $this->syncPriceLists($campaign, $priceListIds);
            }
            $this->apply($campaign, 'campaign_saved');
            $this->audit->record($campaign->wasRecentlyCreated ? 'promotion.campaign.created' : 'promotion.campaign.updated', 'campaign', $campaign->id, ['code' => $campaign->code]);

            return $campaign;
        });
    }

    /**
     * Kích hoạt: thành viên bật theo lịch campaign (chạy khi tới giờ bắt đầu).
     */
    public function activate(Campaign $campaign): void
    {
        $this->transition($campaign, Campaign::ACTIVE, [Campaign::DRAFT], 'campaign_activated');
    }

    /**
     * Dừng khẩn cấp: mọi thành viên tắt ngay.
     */
    public function stop(Campaign $campaign): void
    {
        $this->transition($campaign, Campaign::STOPPED, [Campaign::DRAFT, Campaign::ACTIVE], 'campaign_stopped');
    }

    /**
     * Chỉ xoá campaign nháp; thành viên được tách ra (giữ trạng thái tắt).
     */
    public function delete(Campaign $campaign): void
    {
        if ($campaign->status !== Campaign::DRAFT) {
            throw ValidationException::withMessages(['campaign' => __('promotion::messages.campaign_not_draft')]);
        }
        DB::transaction(function () use ($campaign): void {
            Promotion::query()->where('campaign_id', $campaign->id)->update(['campaign_id' => null]);
            $campaign->delete();
            $this->audit->record('promotion.campaign.deleted', 'campaign', $campaign->id, ['code' => $campaign->code]);
        });
    }

    /**
     * @return list<int>
     */
    public function priceListIds(Campaign $campaign): array
    {
        return DB::table('campaign_price_lists')->where('campaign_id', $campaign->id)->orderBy('price_list_id')->pluck('price_list_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * Kết quả: đơn dùng khuyến mãi của campaign (doanh thu, giảm giá khuyến mãi, số khách). Đơn chỉ hưởng giá sale của
     * bảng giá campaign mà không dùng khuyến mãi chưa được tính (dòng đơn chưa lưu nguồn bảng giá).
     *
     * @return array{totals: SalesTotals, promotion_discount: int, usages: int}
     */
    public function report(Campaign $campaign): array
    {
        $usages = DB::table('promotion_usages')->whereIn('promotion_id', Promotion::query()->where('campaign_id', $campaign->id)->select('id'))->where('status', 'applied');

        return [
            'totals' => $this->statistics->summarize((clone $usages)->distinct()->pluck('order_id')->map(fn ($id): int => (int) $id)->all()),
            'promotion_discount' => (int) (clone $usages)->sum('discount_amount'),
            'usages' => (clone $usages)->count(),
        ];
    }

    /**
     * @param  list<string>  $from
     */
    private function transition(Campaign $campaign, string $to, array $from, string $reason): void
    {
        DB::transaction(function () use ($campaign, $to, $from, $reason): void {
            $locked = Campaign::query()->whereKey($campaign->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, $from, true)) {
                throw ValidationException::withMessages(['campaign' => __('promotion::messages.campaign_transition_invalid')]);
            }
            $locked->update(['status' => $to, 'stopped_at' => $to === Campaign::STOPPED ? now() : null, 'lock_version' => $locked->lock_version + 1]);
            $this->apply($locked, $reason);
            $this->audit->record("promotion.{$reason}", 'campaign', $locked->id, ['code' => $locked->code]);
        });
        $campaign->refresh();
    }

    /**
     * Ghi lịch + trạng thái của campaign xuống mọi thành viên.
     */
    private function apply(Campaign $campaign, string $reason): void
    {
        $on = $campaign->status === Campaign::ACTIVE;
        Promotion::query()->where('campaign_id', $campaign->id)->update([
            'status' => $on ? 'active' : 'inactive', 'starts_at' => $campaign->starts_at, 'ends_at' => $campaign->ends_at, 'updated_at' => now(),
        ]);
        foreach ($this->priceListIds($campaign) as $listId) {
            $this->priceLists->schedule($listId, $campaign->starts_at, $campaign->ends_at, $on, "campaign:{$campaign->code}:{$reason}");
        }
    }

    /**
     * @param  list<int>  $promotionIds
     */
    private function syncPromotions(Campaign $campaign, array $promotionIds): void
    {
        $taken = Promotion::query()->whereKey($promotionIds)->whereNotNull('campaign_id')->where('campaign_id', '!=', $campaign->id)->pluck('name')->all();
        if ($taken !== []) {
            throw ValidationException::withMessages(['promotion_ids' => __('promotion::messages.campaign_member_taken', ['names' => implode(', ', $taken)])]);
        }
        Promotion::query()->where('campaign_id', $campaign->id)->whereNotIn('id', $promotionIds)->update(['campaign_id' => null]);
        Promotion::query()->whereKey($promotionIds)->update(['campaign_id' => $campaign->id]);
    }

    /**
     * @param  list<int>  $priceListIds
     */
    private function syncPriceLists(Campaign $campaign, array $priceListIds): void
    {
        $allowed = array_column($this->priceLists->schedulable(), 'id');
        if (array_diff($priceListIds, $allowed) !== []) {
            throw ValidationException::withMessages(['price_list_ids' => __('promotion::messages.campaign_price_list_invalid')]);
        }
        $taken = DB::table('campaign_price_lists')->whereIn('price_list_id', $priceListIds)->where('campaign_id', '!=', $campaign->id)->exists();
        if ($taken) {
            throw ValidationException::withMessages(['price_list_ids' => __('promotion::messages.campaign_member_taken', ['names' => 'bảng giá'])]);
        }
        DB::table('campaign_price_lists')->where('campaign_id', $campaign->id)->whereNotIn('price_list_id', $priceListIds)->delete();
        foreach (array_diff($priceListIds, $this->priceListIds($campaign)) as $listId) {
            DB::table('campaign_price_lists')->insert(['campaign_id' => $campaign->id, 'price_list_id' => $listId, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
