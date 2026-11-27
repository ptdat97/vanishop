<?php

declare(strict_types=1);

namespace Modules\Promotion\Application;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Pricing\Contracts\PriceListSchedule;
use Modules\Promotion\Contracts\Campaigns;
use Modules\Promotion\Contracts\Data\CampaignUsage;
use Modules\Promotion\Contracts\Data\ManagedCampaign;
use Modules\Promotion\Persistence\Models\Campaign;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Shared\Domain\Schedule\WeeklySchedule;

/**
 * Campaign (roadmap Phase 8): gói khuyến mãi + bảng giá sale/member chạy chung lịch. Campaign SỞ HỮU lịch: khi kích hoạt,
 * sửa lịch, thêm thành viên hay dừng, lịch + trạng thái được ghi xuống từng thành viên (khuyến mãi trực tiếp, bảng giá
 * qua PriceListSchedule) — thành viên tự kiểm tra khung giờ khi tính giá/khuyến mãi, không cần job định kỳ.
 *
 * Nháp: thành viên tắt. Đang chạy (active): thành viên bật với khung giờ của campaign. Dừng (stopped, dừng khẩn cấp):
 * mọi thành viên tắt ngay, không bật lại được (tạo campaign mới).
 */
final class CampaignService implements Campaigns
{
    public function __construct(
        private readonly PriceListSchedule $priceLists,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  array{code: string, name: string, description: ?string, starts_at: string, ends_at: string, recurrence?: mixed}  $data
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
        $this->ensureNotManaged($campaign);
        if ($campaign?->status === Campaign::STOPPED) {
            throw ValidationException::withMessages(['campaign' => __('promotion::messages.campaign_stopped')]);
        }

        return DB::transaction(function () use ($data, $startsAt, $endsAt, $promotionIds, $priceListIds, $campaign, $expectedLockVersion): Campaign {
            if ($campaign !== null && Campaign::query()->whereKey($campaign->id)->where('lock_version', $expectedLockVersion)->increment('lock_version') === 0) {
                throw ValidationException::withMessages(['lock_version' => __('promotion::messages.stale')]);
            }
            $campaign ??= new Campaign(['status' => Campaign::DRAFT]);
            $campaign->fill(['code' => $data['code'], 'name' => $data['name'], 'description' => $data['description'], 'starts_at' => $startsAt, 'ends_at' => $endsAt,
                'recurrence' => WeeklySchedule::fromArray($data['recurrence'] ?? null)?->toArray()])->save();
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
        $this->ensureNotManaged($campaign);
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
     * Số liệu Promotion sở hữu của chiến dịch: đơn đã áp khuyến mãi, tổng giảm, số lượt, mã bảng giá thuộc chiến dịch.
     * Doanh số đơn do bên báo cáo tổng hợp qua `OrderStatistics` (vd. tab "Kết quả" của `vani.reports`).
     */
    public function usage(int $campaignId): ?CampaignUsage
    {
        $campaign = Campaign::query()->find($campaignId);
        if ($campaign === null) {
            return null;
        }
        $usages = DB::table('promotion_usages')->whereIn('promotion_id', Promotion::query()->where('campaign_id', $campaign->id)->select('id'))->where('status', 'applied');
        $listIds = $this->priceListIds($campaign);

        return new CampaignUsage(
            campaignId: $campaign->id,
            promotionOrderIds: (clone $usages)->distinct()->pluck('order_id')->map(fn ($id): int => (int) $id)->values()->all(),
            promotionDiscount: (int) (clone $usages)->sum('discount_amount'),
            usages: (clone $usages)->count(),
            priceListCodes: array_values(array_map(fn (array $list): string => $list['code'], $this->priceLists->describe($listIds))),
        );
    }

    /**
     * @param  list<string>  $from
     */
    private function transition(Campaign $campaign, string $to, array $from, string $reason): void
    {
        $this->ensureNotManaged($campaign);
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
        $recurrence = WeeklySchedule::fromArray($campaign->recurrence);
        Promotion::query()->where('campaign_id', $campaign->id)->update([
            'status' => $on ? 'active' : 'inactive', 'starts_at' => $campaign->starts_at, 'ends_at' => $campaign->ends_at,
            'recurrence' => $recurrence === null ? null : json_encode($recurrence->toArray(), JSON_THROW_ON_ERROR), 'updated_at' => now(),
        ]);
        foreach ($this->priceListIds($campaign) as $listId) {
            $this->priceLists->schedule($listId, $campaign->starts_at, $campaign->ends_at, $on, "campaign:{$campaign->code}:{$reason}", $recurrence);
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

    public function sync(string $owner, string $key, ManagedCampaign $managed): int
    {
        if (! in_array($managed->status, [ManagedCampaign::DRAFT, ManagedCampaign::ACTIVE, ManagedCampaign::STOPPED], true)) {
            throw ValidationException::withMessages(['status' => __('promotion::messages.campaign_transition_invalid')]);
        }
        if ($managed->endsAt <= $managed->startsAt) {
            throw ValidationException::withMessages(['ends_at' => __('promotion::messages.campaign_window_invalid')]);
        }

        return DB::transaction(function () use ($owner, $key, $managed): int {
            $campaign = Campaign::query()->where('managed_by', $owner)->where('managed_key', $key)->lockForUpdate()->first()
                ?? new Campaign(['code' => $this->managedCode($owner, $key), 'managed_by' => $owner, 'managed_key' => $key]);
            $campaign->fill([
                'name' => $managed->name, 'description' => $managed->description, 'managed_route' => $managed->editRoute,
                'starts_at' => Carbon::createFromTimestamp($managed->startsAt), 'ends_at' => Carbon::createFromTimestamp($managed->endsAt),
                'recurrence' => $managed->recurrence?->toArray(), 'status' => $managed->status,
                'stopped_at' => $managed->status === ManagedCampaign::STOPPED ? ($campaign->stopped_at ?? now()) : null,
                'lock_version' => ($campaign->lock_version ?? 0) + 1,
            ])->save();

            $this->syncPromotions($campaign, $managed->promotionIds);
            $this->syncPriceLists($campaign, $managed->priceListIds, $owner);
            $this->apply($campaign, 'campaign_synced');
            $this->audit->record($campaign->wasRecentlyCreated ? 'promotion.campaign.created' : 'promotion.campaign.updated', 'campaign', $campaign->id, ['code' => $campaign->code, 'owner' => $owner]);

            return $campaign->id;
        });
    }

    public function remove(string $owner, string $key): void
    {
        DB::transaction(function () use ($owner, $key): void {
            $campaign = Campaign::query()->where('managed_by', $owner)->where('managed_key', $key)->lockForUpdate()->first();
            if ($campaign === null) {
                return;
            }
            Promotion::query()->where('campaign_id', $campaign->id)->update(['campaign_id' => null]);
            $campaign->delete();
            $this->audit->record('promotion.campaign.deleted', 'campaign', $campaign->id, ['code' => $campaign->code, 'owner' => $owner]);
        });
    }

    public function ids(string $owner): array
    {
        return Campaign::query()->where('managed_by', $owner)->whereNotNull('managed_key')->pluck('id', 'managed_key')->map(fn ($id): int => (int) $id)->all();
    }

    /** Campaign của plugin (`sync`): chỉ plugin sửa, Admin chỉ xem. */
    private function ensureNotManaged(?Campaign $campaign): void
    {
        if ($campaign?->isManaged() === true) {
            throw ValidationException::withMessages(['campaign' => __('promotion::messages.campaign_managed')]);
        }
    }

    private function managedCode(string $owner, string $key): string
    {
        $base = Str::limit(Str::slug(str_replace('.', '-', $owner).'-'.$key), 56, '');
        $code = $base;
        for ($suffix = 2; Campaign::query()->where('code', $code)->exists(); $suffix++) {
            $code = "{$base}-{$suffix}";
        }

        return $code;
    }

    /**
     * Bảng giá thành viên: bảng giá sale/thành viên của Admin, hoặc của chính plugin quản lý campaign (`$owner`).
     *
     * @param  list<int>  $priceListIds
     */
    private function syncPriceLists(Campaign $campaign, array $priceListIds, ?string $owner = null): void
    {
        $allowed = array_column($this->priceLists->schedulable(), 'id');
        if ($owner !== null) {
            foreach ($this->priceLists->describe($priceListIds) as $list) {
                if ($list['managed_by'] === $owner) {
                    $allowed[] = $list['id'];
                }
            }
        }
        if (array_diff($priceListIds, $allowed) !== []) {
            throw ValidationException::withMessages(['price_list_ids' => __('promotion::messages.campaign_price_list_invalid')]);
        }
        $taken = DB::table('campaign_price_lists')->whereIn('price_list_id', $priceListIds)->where('campaign_id', '!=', $campaign->id)->exists();
        if ($taken) {
            throw ValidationException::withMessages(['price_list_ids' => __('promotion::messages.campaign_member_taken', ['names' => __('promotion::texts.campaign_service.price_list')])]);
        }
        DB::table('campaign_price_lists')->where('campaign_id', $campaign->id)->whereNotIn('price_list_id', $priceListIds)->delete();
        foreach (array_diff($priceListIds, $this->priceListIds($campaign)) as $listId) {
            DB::table('campaign_price_lists')->insert(['campaign_id' => $campaign->id, 'price_list_id' => $listId, 'created_at' => now(), 'updated_at' => now()]);
        }
    }
}
