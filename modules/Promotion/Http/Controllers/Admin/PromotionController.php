<?php

declare(strict_types=1);

namespace Modules\Promotion\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Promotion\Application\AdminQueries;
use Modules\Promotion\Application\PromotionRegistry;
use Modules\Promotion\Application\PromotionService;
use Modules\Promotion\Contracts\ContextualPromotionAction;
use Modules\Promotion\Contracts\PromotionAction;
use Modules\Promotion\Domain\Stacking;
use Modules\Promotion\Http\Requests\PromotionRequest;
use Modules\Promotion\Http\Requests\VoucherRequest;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Promotion\Persistence\Models\Voucher;
use Modules\Shared\Http\ListSort;
use Modules\Shared\Support\StoreClock;

final class PromotionController
{
    public function __construct(private readonly AdminQueries $queries) {}

    public function home(): RedirectResponse
    {
        Gate::authorize('promotion.view');

        return redirect()->route('admin.promotion.promotions.index');
    }

    public function index(Request $request, AdminScreen $screen): Response
    {
        Gate::authorize('promotion.view');
        $extensionFilters = array_filter((array) $request->query('ext', []), 'is_string');
        $extensionIds = $screen->filterIds('promotion', $extensionFilters);
        $sort = ListSort::fromRequest($request, ['name' => 'name', 'period' => 'starts_at', 'usage' => 'usage_count']);
        $promotions = $this->queries->promotions($extensionIds, $sort);

        return Inertia::render('Promotion::Promotions/Index', [
            'baseUrl' => route('admin.promotion.promotions.index'),
            'labels' => __('promotion::labels'),
            'sort' => $sort?->toArray(),
            'promotions' => $promotions->map(fn (Promotion $promotion): array => [
                'id' => $promotion->id,
                'name' => $promotion->name,
                'managed' => $promotion->isManaged(),
                // Khuyến mãi do plugin quản lý: mở thẳng màn hình sửa của plugin (plugin tắt → form chỉ xem của Core).
                'edit_url' => $promotion->managedUrl() ?? route('admin.promotion.promotions.edit', $promotion->id),
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
            // Cột/lọc/thao tác hàng loạt/công cụ của plugin (EP v3 X1).
            'extensions' => $screen->listProps('promotion', $promotions->pluck('id')->map(fn ($id): int => (int) $id)->all(), $extensionFilters),
        ]);
    }

    public function create(PromotionRegistry $registry): Response
    {
        Gate::authorize('promotion.manage');

        return $this->form(null, $registry, null);
    }

    public function store(PromotionRequest $request, PromotionService $service): RedirectResponse
    {
        $promotion = $service->save($request->toData());

        return redirect()->route('admin.promotion.promotions.edit', ['promotion' => $promotion->id])->with('success', __('promotion::messages.saved'));
    }

    public function edit(Promotion $promotion, PromotionRegistry $registry, Request $request): Response
    {
        if (! $promotion->isManaged() || Gate::denies('promotion.view')) {
            Gate::authorize('promotion.manage');
        }

        return $this->form($promotion, $registry, $request);
    }

    public function update(Promotion $promotion, PromotionRequest $request, PromotionService $service): RedirectResponse
    {
        $this->assertNotManaged($promotion);
        $service->save($request->toData(), $promotion, (int) $request->validated('lock_version'));

        return back()->with('success', __('promotion::messages.saved'));
    }

    public function destroy(Promotion $promotion, PromotionService $service): RedirectResponse
    {
        Gate::authorize('promotion.manage');
        $this->assertNotManaged($promotion);
        $service->delete($promotion);

        return redirect()->route('admin.promotion.promotions.index')->with('success', __('promotion::messages.deleted'));
    }

    public function storeVouchers(Promotion $promotion, VoucherRequest $request, PromotionService $service): RedirectResponse
    {
        $this->assertNotManaged($promotion);
        $count = count($service->createVouchers(
            $promotion,
            $request->validated('code'),
            $request->validated('prefix'),
            (int) ($request->validated('count') ?? 1),
            $request->validated('usage_limit') === null ? null : (int) $request->validated('usage_limit'),
            $request->validated('expires_at'),
        ));

        return back()->with('success', __('promotion::messages.vouchers_created', ['count' => $count]));
    }

    public function updateVoucher(Promotion $promotion, Voucher $voucher, Request $request, PromotionService $service): RedirectResponse
    {
        Gate::authorize('promotion.manage');
        abort_unless($voucher->promotion_id === $promotion->id, 404);
        $this->assertNotManaged($promotion);
        $data = $request->validate(['status' => ['required', Rule::in(['active', 'inactive'])]]);
        $service->setVoucherStatus($voucher, $data['status']);

        return back()->with('success', __('promotion::messages.saved'));
    }

    private function assertNotManaged(Promotion $promotion): void
    {
        if ($promotion->isManaged()) {
            throw ValidationException::withMessages(['business' => __('promotion::messages.managed', ['owner' => $promotion->managed_by])]);
        }
    }

    /** Số mã giảm giá mỗi trang trong form khuyến mãi (backlog L32: trước chỉ hiện 200 mã mới nhất, không tìm được). */
    private const VOUCHERS_PER_PAGE = 50;

    private function form(?Promotion $promotion, PromotionRegistry $registry, ?Request $request): Response
    {
        $actions = array_filter($registry->actions(), fn (PromotionAction $action): bool => ! $action instanceof ContextualPromotionAction
            || $action->type() === $promotion?->action_type);
        $voucherQuery = trim((string) $request?->query('voucher_q', ''));
        $vouchers = $promotion?->vouchers()
            ->when($voucherQuery !== '', fn ($query) => $query->where('code', 'like', '%'.addcslashes(Voucher::normalize($voucherQuery), '%_\\').'%'))
            ->orderByDesc('id')
            ->paginate(self::VOUCHERS_PER_PAGE, pageName: 'voucher_page')
            ->withQueryString();

        return Inertia::render('Promotion::Promotions/Form', [
            'baseUrl' => route('admin.promotion.promotions.index'),
            'labels' => __('promotion::labels'),
            'promotion' => $promotion === null ? null : [
                ...$promotion->only(['id', 'name', 'status', 'priority', 'requires_voucher', 'action_type', 'action_config', 'usage_limit', 'usage_count', 'usage_limit_per_customer', 'budget_amount', 'budget_used_amount', 'lock_version']),
                'stacking' => $promotion->stacking->value,
                'starts_at' => StoreClock::toInput($promotion->starts_at),
                'ends_at' => StoreClock::toInput($promotion->ends_at),
                'recurrence' => $promotion->recurrence,
                // Do plugin quản lý (ManagedPromotions): Admin chỉ xem, sửa tại màn hình của plugin.
                'managed' => $promotion->isManaged() ? ['owner' => $promotion->managed_by, 'url' => $promotion->managedUrl()] : null,
                'rules' => $promotion->rules->map(fn ($rule): array => ['type' => $rule->rule_type, 'label' => $registry->rule($rule->rule_type)?->label(), 'config' => $rule->config])->all(),
            ],
            'vouchers' => $vouchers === null ? [] : collect($vouchers->items())->map(fn (Voucher $voucher): array => [
                'id' => $voucher->id,
                'code' => $voucher->code,
                'status' => $voucher->status,
                'used_count' => $voucher->used_count,
                'usage_limit' => $voucher->usage_limit,
                'customer_id' => $voucher->customer_id,
                'expires_at' => StoreClock::format($voucher->expires_at),
            ])->all(),
            'vouchersTotal' => $promotion?->vouchers()->count() ?? 0,
            // Phân trang + tìm theo mã (chuẩn hoá như khi tạo mã): khuyến mãi có hàng nghìn mã riêng cho từng khách.
            'voucherFilters' => ['q' => $voucherQuery],
            'voucherPagination' => $vouchers === null ? null : [
                'page' => $vouchers->currentPage(), 'last_page' => $vouchers->lastPage(), 'from' => $vouchers->firstItem(),
                'to' => $vouchers->lastItem(), 'total' => $vouchers->total(), 'per_page' => $vouchers->perPage(),
            ],
            'actions' => array_map(fn (PromotionAction $action): array => ['type' => $action->type(), 'label' => $action->label()], array_values($actions)),
            'ruleTypes' => array_map(fn ($rule): array => ['type' => $rule->type(), 'label' => $rule->label()], array_values($registry->rules())),
            'stackings' => array_column(Stacking::cases(), 'value'),
        ]);
    }
}
