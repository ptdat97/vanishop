<?php

declare(strict_types=1);

namespace Modules\Promotion\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Pricing\Contracts\PriceListSchedule;
use Modules\Promotion\Application\CampaignService;
use Modules\Promotion\Persistence\Models\Campaign;
use Modules\Promotion\Persistence\Models\Promotion;

/**
 * Campaign (roadmap Phase 8): gói khuyến mãi + bảng giá chạy chung lịch, kích hoạt/dừng khẩn cấp một nút, báo cáo kết
 * quả. Gắn bảng giá cần thêm quyền `pricing.manage`.
 */
final class CampaignController
{
    private const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public function index(CampaignService $campaigns): Response
    {
        Gate::authorize('promotion.view');

        return Inertia::render('Promotion::Campaigns/Index', [
            'baseUrl' => route('admin.promotion.campaigns.index'),
            'promotionsUrl' => route('admin.promotion.promotions.index'),
            'campaigns' => Campaign::query()->withCount('promotions')->orderByDesc('starts_at')->get()->map(fn (Campaign $campaign): array => [
                'id' => $campaign->id, 'code' => $campaign->code, 'name' => $campaign->name, 'state' => $campaign->state(),
                'starts_at' => $campaign->starts_at->timezone(self::TIMEZONE)->format('d/m/Y H:i'),
                'ends_at' => $campaign->ends_at->timezone(self::TIMEZONE)->format('d/m/Y H:i'),
                'promotions_count' => (int) $campaign->getAttribute('promotions_count'),
                'price_lists_count' => count($campaigns->priceListIds($campaign)),
            ])->all(),
            'canManage' => Gate::allows('promotion.manage'),
        ]);
    }

    public function create(PriceListSchedule $priceLists): Response
    {
        Gate::authorize('promotion.manage');

        return $this->form(null, $priceLists, app(CampaignService::class));
    }

    public function store(Request $request, CampaignService $campaigns): RedirectResponse
    {
        Gate::authorize('promotion.manage');
        [$data, $promotionIds, $priceListIds] = $this->validated($request);
        $campaign = $campaigns->save($data, $promotionIds, $priceListIds);

        return redirect()->route('admin.promotion.campaigns.edit', ['campaign' => $campaign->id])->with('success', __('promotion::messages.saved'));
    }

    public function edit(Campaign $campaign, PriceListSchedule $priceLists, CampaignService $campaigns): Response
    {
        Gate::authorize('promotion.view');

        return $this->form($campaign, $priceLists, $campaigns);
    }

    public function update(Campaign $campaign, Request $request, CampaignService $campaigns): RedirectResponse
    {
        Gate::authorize('promotion.manage');
        [$data, $promotionIds, $priceListIds] = $this->validated($request, $campaign);
        $campaigns->save($data, $promotionIds, $priceListIds, $campaign, (int) $request->input('lock_version'));

        return back()->with('success', __('promotion::messages.saved'));
    }

    public function destroy(Campaign $campaign, CampaignService $campaigns): RedirectResponse
    {
        Gate::authorize('promotion.manage');
        $campaigns->delete($campaign);

        return redirect()->route('admin.promotion.campaigns.index')->with('success', __('promotion::messages.deleted'));
    }

    public function activate(Campaign $campaign, CampaignService $campaigns): RedirectResponse
    {
        Gate::authorize('promotion.manage');
        $campaigns->activate($campaign);

        return back()->with('success', __('promotion::messages.campaign_activated'));
    }

    public function stop(Campaign $campaign, CampaignService $campaigns): RedirectResponse
    {
        Gate::authorize('promotion.manage');
        $campaigns->stop($campaign);

        return back()->with('success', __('promotion::messages.campaign_stopped_now'));
    }

    private function form(?Campaign $campaign, PriceListSchedule $priceLists, CampaignService $campaigns): Response
    {
        $report = $campaign === null ? null : $campaigns->report($campaign);

        return Inertia::render('Promotion::Campaigns/Form', [
            'baseUrl' => route('admin.promotion.campaigns.index'),
            'campaign' => $campaign === null ? null : [
                'id' => $campaign->id, 'code' => $campaign->code, 'name' => $campaign->name, 'description' => $campaign->description,
                'starts_at' => $campaign->starts_at->timezone(self::TIMEZONE)->format('Y-m-d\TH:i'),
                'ends_at' => $campaign->ends_at->timezone(self::TIMEZONE)->format('Y-m-d\TH:i'),
                'status' => $campaign->status, 'state' => $campaign->state(), 'lock_version' => $campaign->lock_version,
                'promotion_ids' => $campaign->promotions()->pluck('id')->all(),
                'price_list_ids' => $campaigns->priceListIds($campaign),
            ],
            'report' => $report === null ? null : [
                'orders' => $report['totals']->ordersCount, 'revenue' => $report['totals']->revenue, 'customers' => $report['totals']->customersCount,
                'cancelled' => $report['totals']->cancelledCount, 'promotion_discount' => $report['promotion_discount'], 'usages' => $report['usages'], 'price_list_orders' => $report['price_list_orders'],
            ],
            'promotions' => Promotion::query()->where(fn ($query) => $query->whereNull('campaign_id')->when($campaign !== null, fn ($q) => $q->orWhere('campaign_id', $campaign?->id)))
                ->orderByDesc('id')->get(['id', 'name', 'requires_voucher'])->map(fn (Promotion $promotion): array => ['id' => $promotion->id, 'name' => $promotion->name, 'requires_voucher' => $promotion->requires_voucher])->all(),
            'priceLists' => $priceLists->schedulable(),
            'can' => ['manage' => Gate::allows('promotion.manage'), 'price_lists' => Gate::allows('pricing.manage')],
        ]);
    }

    /**
     * @return array{0: array{code: string, name: string, description: ?string, starts_at: string, ends_at: string}, 1: list<int>, 2: list<int>|null}
     */
    private function validated(Request $request, ?Campaign $campaign = null): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_-]*$/', Rule::unique('campaigns', 'code')->ignore($campaign?->id)],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date'],
            'promotion_ids' => ['array'],
            'promotion_ids.*' => ['integer', 'exists:promotions,id'],
            'price_list_ids' => ['array'],
            'price_list_ids.*' => ['integer'],
        ]);
        $toUtc = fn (string $local): string => Carbon::parse($local, self::TIMEZONE)->utc()->toDateTimeString();

        return [
            ['code' => $data['code'], 'name' => $data['name'], 'description' => $data['description'] ?? null, 'starts_at' => $toUtc($data['starts_at']), 'ends_at' => $toUtc($data['ends_at'])],
            array_map('intval', $data['promotion_ids'] ?? []),
            // Không có quyền bảng giá → không đụng tới danh sách bảng giá của campaign.
            Gate::allows('pricing.manage') ? array_map('intval', $data['price_list_ids'] ?? []) : null,
        ];
    }
}
