<?php

declare(strict_types=1);

namespace Modules\Promotion\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Promotion\Application\PromotionRegistry;
use Modules\Promotion\Application\PromotionService;
use Modules\Promotion\Domain\Stacking;
use Modules\Promotion\Http\Requests\PromotionRequest;
use Modules\Promotion\Http\Requests\VoucherRequest;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Promotion\Persistence\Models\Voucher;
use Modules\Shared\Support\StoreClock;

final class PromotionController
{
    public function home(): RedirectResponse
    {
        Gate::authorize('promotion.view');

        return redirect()->route('admin.promotion.promotions.index');
    }

    public function index(): Response
    {
        Gate::authorize('promotion.view');

        return Inertia::render('Promotion::Promotions/Index', [
            'baseUrl' => route('admin.promotion.promotions.index'),
            'promotions' => Promotion::query()->withCount('vouchers')->orderByDesc('priority')->orderByDesc('id')->get()->map(fn (Promotion $promotion): array => [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'status' => $promotion->status,
                'running' => $promotion->isRunningAt(now()->getTimestamp()),
                'action_type' => $promotion->action_type,
                'action_config' => $promotion->action_config,
                'requires_voucher' => $promotion->requires_voucher,
                'stacking' => $promotion->stacking->value,
                'priority' => $promotion->priority,
                'usage_count' => $promotion->usage_count,
                'usage_limit' => $promotion->usage_limit,
                'vouchers_count' => $promotion->vouchers_count,
                'starts_at' => StoreClock::format($promotion->starts_at),
                'ends_at' => StoreClock::format($promotion->ends_at),
            ])->all(),
            'canManage' => Gate::allows('promotion.manage'),
            'campaignsUrl' => route('admin.promotion.campaigns.index'),
        ]);
    }

    public function create(PromotionRegistry $registry): Response
    {
        Gate::authorize('promotion.manage');

        return $this->form(null, $registry);
    }

    public function store(PromotionRequest $request, PromotionService $service): RedirectResponse
    {
        $promotion = $service->save($request->toData());

        return redirect()->route('admin.promotion.promotions.edit', ['promotion' => $promotion->id])->with('success', __('promotion::messages.saved'));
    }

    public function edit(Promotion $promotion, PromotionRegistry $registry): Response
    {
        Gate::authorize('promotion.manage');

        return $this->form($promotion, $registry);
    }

    public function update(Promotion $promotion, PromotionRequest $request, PromotionService $service): RedirectResponse
    {
        $service->save($request->toData(), $promotion, (int) $request->validated('lock_version'));

        return back()->with('success', __('promotion::messages.saved'));
    }

    public function destroy(Promotion $promotion, PromotionService $service): RedirectResponse
    {
        Gate::authorize('promotion.manage');
        $service->delete($promotion);

        return redirect()->route('admin.promotion.promotions.index')->with('success', __('promotion::messages.deleted'));
    }

    public function storeVouchers(Promotion $promotion, VoucherRequest $request, PromotionService $service): RedirectResponse
    {
        $count = $service->createVouchers(
            $promotion,
            $request->validated('code'),
            $request->validated('prefix'),
            (int) ($request->validated('count') ?? 1),
            $request->validated('usage_limit') === null ? null : (int) $request->validated('usage_limit'),
            $request->validated('expires_at'),
        );

        return back()->with('success', __('promotion::messages.vouchers_created', ['count' => $count]));
    }

    public function updateVoucher(Promotion $promotion, Voucher $voucher, Request $request, PromotionService $service): RedirectResponse
    {
        Gate::authorize('promotion.manage');
        abort_unless($voucher->promotion_id === $promotion->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'inactive'])]]);
        $service->setVoucherStatus($voucher, $data['status']);

        return back()->with('success', __('promotion::messages.saved'));
    }

    private function form(?Promotion $promotion, PromotionRegistry $registry): Response
    {
        return Inertia::render('Promotion::Promotions/Form', [
            'baseUrl' => route('admin.promotion.promotions.index'),
            'promotion' => $promotion === null ? null : [
                ...$promotion->only(['id', 'name', 'status', 'priority', 'requires_voucher', 'action_type', 'action_config', 'usage_limit', 'usage_count', 'budget_amount', 'budget_used_amount', 'lock_version']),
                'stacking' => $promotion->stacking->value,
                'starts_at' => StoreClock::toInput($promotion->starts_at),
                'ends_at' => StoreClock::toInput($promotion->ends_at),
                'rules' => $promotion->rules->map(fn ($rule): array => ['type' => $rule->rule_type, 'label' => $registry->rule($rule->rule_type)?->label(), 'config' => $rule->config])->all(),
            ],
            'vouchers' => $promotion === null ? [] : $promotion->vouchers()->orderByDesc('id')->limit(200)->get()->map(fn (Voucher $voucher): array => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'status' => $voucher->status,
                'used_count' => $voucher->used_count,
                'usage_limit' => $voucher->usage_limit,
                'expires_at' => StoreClock::format($voucher->expires_at),
            ])->all(),
            'vouchersTotal' => $promotion?->vouchers()->count() ?? 0,
            'actions' => array_map(fn ($action): array => ['type' => $action->type(), 'label' => $action->label()], array_values($registry->actions())),
            'ruleTypes' => array_map(fn ($rule): array => ['type' => $rule->type(), 'label' => $rule->label()], array_values($registry->rules())),
            'stackings' => array_column(Stacking::cases(), 'value'),
        ]);
    }
}
