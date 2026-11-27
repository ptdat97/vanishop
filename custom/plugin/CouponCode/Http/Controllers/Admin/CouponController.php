<?php

declare(strict_types=1);

namespace Plugin\CouponCode\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\CatalogLookup;
use Modules\Customer\Contracts\CustomerDirectory;
use Modules\Customer\Contracts\CustomerSegments;
use Modules\Customer\Contracts\Data\CustomerSummary;
use Modules\Promotion\Contracts\Data\VoucherData;
use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Promotion\Contracts\Vouchers;
use Modules\Shared\Support\StoreClock;
use Modules\Shared\Support\StoreLocale;
use Plugin\CouponCode\Application\Coupons;
use Plugin\CouponCode\CouponCodeServiceProvider;
use Plugin\CouponCode\Domain\CouponTerms;
use Plugin\CouponCode\Infrastructure\IssueCouponCodes;
use Plugin\CouponCode\Persistence\Coupon;

/**
 * Admin → Marketing → Mã giảm giá. Mỗi mã đồng bộ thành một khuyến mãi cần nhập mã ở Admin → Khuyến mãi (chỉ xem ở đó).
 */
final class CouponController
{
    /** Phạm vi → loại tìm của CatalogLookup. */
    private const LOOKUP_KINDS = ['products' => 'product', 'categories' => 'category', 'collections' => 'collection', 'brands' => 'brand'];

    private const ISSUED_SHOWN = 50;

    public function __construct(
        private readonly Coupons $coupons,
        private readonly CatalogLookup $catalog,
        private readonly CustomerSegments $segments,
    ) {}

    public function index(ManagedPromotions $promotions): Response
    {
        Gate::authorize('coupon_code.view');
        $now = now()->getTimestamp();
        $usage = $promotions->usage(CouponCodeServiceProvider::ID);

        return Inertia::render('CouponCode::Index', [
            'coupons' => Coupon::query()->orderByDesc('id')->get()->map(fn (Coupon $coupon): array => [
                'id' => $coupon->id,
                'name' => $coupon->name,
                'code' => $coupon->kind === Coupon::KIND_SHARED ? $coupon->code : null,
                'kind' => $coupon->kind,
                'offer' => $coupon->discount_type === 'percent' ? ['percent' => $coupon->discount_value, 'max' => $coupon->max_discount] : ['amount' => $coupon->discount_value],
                'min_subtotal' => $coupon->min_subtotal,
                'used' => $usage[$coupon->id]->usageCount ?? 0,
                'discount_total' => $usage[$coupon->id]->discountAmount ?? 0,
                'usage_limit' => $coupon->usage_limit,
                'state' => $this->state($coupon, $now),
                'public' => $coupon->public,
                'period' => array_values(array_filter([StoreClock::format($coupon->starts_at), StoreClock::format($coupon->ends_at)])),
                'edit_url' => route('admin.plugins.vani-coupon-code.edit', $coupon->id),
            ])->all(),
            'couponUrls' => ['create' => route('admin.plugins.vani-coupon-code.create')],
            'can' => ['manage' => Gate::allows('coupon_code.manage')],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('coupon_code.manage');

        return $this->form(null);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('coupon_code.manage');
        $coupon = $this->coupons->save($this->validated($request, null));

        return redirect()->route('admin.plugins.vani-coupon-code.edit', $coupon->id)->with('success', __('vani-coupon-code::texts.saved'));
    }

    public function edit(Coupon $coupon, Vouchers $vouchers, CustomerDirectory $customers): Response
    {
        Gate::authorize('coupon_code.view');

        return $this->form($coupon, $vouchers, $customers);
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        Gate::authorize('coupon_code.manage');
        $data = $this->validated($request, $coupon);
        $this->coupons->save($data, $coupon, (int) $request->input('lock_version'));

        return back()->with('success', __('vani-coupon-code::texts.saved'));
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        Gate::authorize('coupon_code.manage');
        $this->coupons->delete($coupon);

        return redirect()->route('admin.plugins.vani-coupon-code.index')->with('success', __('vani-coupon-code::texts.deleted'));
    }

    /** Sinh thêm mã dùng chung (in phiếu, gửi KOL…). */
    public function generate(Request $request, Coupon $coupon): RedirectResponse
    {
        Gate::authorize('coupon_code.manage');
        abort_unless($coupon->kind === Coupon::KIND_SHARED, 404);
        $data = $request->validate([
            'prefix' => ['required', 'string', 'max:12', 'regex:/^[A-Za-z0-9-]+$/'],
            'count' => ['required', 'integer', 'min:1', 'max:5000'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ]);
        $codes = $this->coupons->generate($coupon, (string) $data['prefix'], (int) $data['count'], isset($data['usage_limit']) ? (int) $data['usage_limit'] : null);

        return back()->with('success', __('vani-coupon-code::texts.generated', ['count' => count($codes)]));
    }

    /** Phát mã riêng từ mã mẫu: khách chọn cụ thể (ngay) hoặc cả nhóm/tag (chạy nền). */
    public function issue(Request $request, Coupon $coupon): RedirectResponse
    {
        Gate::authorize('coupon_code.manage');
        abort_unless($coupon->kind === Coupon::KIND_TEMPLATE, 404);
        $data = $request->validate([
            'target' => ['required', Rule::in(['customers', 'segment'])],
            'customer_ids' => ['required_if:target,customers', 'array', 'max:200'],
            'customer_ids.*' => ['integer', 'min:1'],
            'group_id' => ['nullable', 'integer', Rule::in(array_column($this->segments->groups(), 'id'))],
            'tag' => ['nullable', 'string', 'max:40'],
        ]);
        if ($data['target'] === 'segment') {
            if (empty($data['group_id']) && trim((string) ($data['tag'] ?? '')) === '') {
                throw ValidationException::withMessages(['group_id' => __('vani-coupon-code::texts.segment_required')]);
            }
            IssueCouponCodes::dispatch($coupon->id, isset($data['group_id']) ? (int) $data['group_id'] : null, trim((string) ($data['tag'] ?? '')) ?: null);

            return back()->with('success', __('vani-coupon-code::texts.issue_queued'));
        }
        $count = $this->coupons->issue($coupon, array_map('intval', $data['customer_ids']), now()->getTimestamp());

        return back()->with('success', __('vani-coupon-code::texts.issued', ['count' => $count]));
    }

    /** Tìm sản phẩm/danh mục/bộ sưu tập/thương hiệu cho ô phạm vi. */
    public function lookup(Request $request): JsonResponse
    {
        Gate::authorize('coupon_code.manage');
        $data = $request->validate(['scope' => ['required', Rule::in(array_keys(self::LOOKUP_KINDS))], 'q' => ['nullable', 'string', 'max:120']]);

        return response()->json(['data' => array_map(fn (array $row): array => [
            'value' => (string) $row['id'],
            'label' => $row['name'].($row['visible'] ? '' : __('vani-coupon-code::texts.hidden')),
        ], $this->catalog->search(self::LOOKUP_KINDS[$data['scope']], (string) ($data['q'] ?? ''), StoreLocale::default()))]);
    }

    public function customers(Request $request, CustomerDirectory $customers): JsonResponse
    {
        Gate::authorize('coupon_code.manage');
        $data = $request->validate(['q' => ['nullable', 'string', 'max:120']]);

        return response()->json(['data' => array_map(fn (CustomerSummary $customer): array => [
            'value' => (string) $customer->id,
            'label' => $this->customerLabel($customer),
        ], $customers->search((string) ($data['q'] ?? '')))]);
    }

    private function form(?Coupon $coupon, ?Vouchers $vouchers = null, ?CustomerDirectory $customers = null): Response
    {
        $all = $coupon?->promotion_id === null || $vouchers === null ? [] : $vouchers->ofPromotion($coupon->promotion_id);
        $issued = array_slice(array_reverse(array_values(array_filter($all, fn (VoucherData $voucher): bool => $voucher->customerId !== null))), 0, self::ISSUED_SHOWN);
        $names = $customers === null || $issued === [] ? [] : $customers->summaries(array_map(fn (VoucherData $voucher): int => (int) $voucher->customerId, $issued));

        return Inertia::render('CouponCode::Form', [
            'coupon' => $coupon === null ? null : [
                'id' => $coupon->id,
                'kind' => $coupon->kind,
                'code' => $coupon->code,
                'prefix' => $coupon->prefix,
                'name' => $coupon->name,
                'description' => $coupon->description(StoreLocale::default()),
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'max_discount' => $coupon->max_discount,
                'min_subtotal' => $coupon->min_subtotal,
                'scope_type' => $coupon->scope_type,
                'scope' => $this->scopeOptions($coupon),
                'exclude_on_sale' => $coupon->exclude_on_sale,
                'customer_groups' => $coupon->customer_groups,
                'usage_limit' => $coupon->usage_limit,
                'usage_per_customer' => $coupon->usage_per_customer,
                'valid_days' => $coupon->valid_days,
                'starts_at' => StoreClock::toInput($coupon->starts_at),
                'ends_at' => StoreClock::toInput($coupon->ends_at),
                'active' => $coupon->active,
                'exclusive' => $coupon->exclusive,
                'public' => $coupon->public,
                'lock_version' => $coupon->lock_version,
                'codes_total' => count($all),
                'issued_total' => count(array_filter($all, fn (VoucherData $voucher): bool => $voucher->customerId !== null)),
                'issued' => array_map(fn (VoucherData $voucher): array => [
                    'code' => $voucher->code,
                    'customer' => isset($names[$voucher->customerId]) ? $this->customerLabel($names[$voucher->customerId]) : '#'.$voucher->customerId,
                    'used' => $voucher->usageLimit !== null && $voucher->usedCount >= $voucher->usageLimit,
                    'expires_at' => $voucher->expiresAt === null ? null : StoreClock::format(now()->setTimestamp($voucher->expiresAt)),
                ], $issued),
                'promotion_url' => $coupon->promotion_id !== null && Gate::allows('promotion.view') ? route('admin.promotion.promotions.edit', $coupon->promotion_id) : null,
            ],
            'groups' => array_map(fn (array $group): array => ['value' => $group['code'], 'id' => $group['id'], 'label' => $group['name']], $this->segments->groups()),
            'limits' => ['max_percent' => CouponTerms::MAX_PERCENT],
            'couponUrls' => [
                'index' => route('admin.plugins.vani-coupon-code.index'),
                'save' => $coupon === null ? route('admin.plugins.vani-coupon-code.store') : route('admin.plugins.vani-coupon-code.update', $coupon->id),
                'delete' => $coupon === null ? null : route('admin.plugins.vani-coupon-code.destroy', $coupon->id),
                'generate' => $coupon === null ? null : route('admin.plugins.vani-coupon-code.generate', $coupon->id),
                'issue' => $coupon === null ? null : route('admin.plugins.vani-coupon-code.issue', $coupon->id),
                'lookup' => route('admin.plugins.vani-coupon-code.lookup'),
                'customers' => route('admin.plugins.vani-coupon-code.customers'),
            ],
            'can' => ['manage' => Gate::allows('coupon_code.manage')],
        ]);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function scopeOptions(Coupon $coupon): array
    {
        if (! isset(self::LOOKUP_KINDS[$coupon->scope_type])) {
            return [];
        }
        $ids = array_map('intval', $coupon->scope_values);
        $locale = StoreLocale::default();
        $names = $coupon->scope_type === 'categories'
            ? array_map(fn (array $row): string => $row['name'], $this->catalog->adminCategories($ids, $locale))
            : array_map(fn (array $row): string => $row['name'], $this->catalog->visible(self::LOOKUP_KINDS[$coupon->scope_type], $ids, $locale));

        return array_map(fn (int $id): array => ['value' => (string) $id, 'label' => $names[$id] ?? '#'.$id.__('vani-coupon-code::texts.hidden')], $ids);
    }

    /** sắp chạy | đang chạy | đã kết thúc | tắt */
    private function state(Coupon $coupon, int $now): string
    {
        return match (true) {
            ! $coupon->active => 'off',
            $coupon->ends_at !== null && $coupon->ends_at->getTimestamp() <= $now => 'ended',
            $coupon->starts_at !== null && $coupon->starts_at->getTimestamp() > $now => 'scheduled',
            default => 'running',
        };
    }

    private function customerLabel(CustomerSummary $customer): string
    {
        return trim(($customer->fullName ?? '').' · '.($customer->phone ?? $customer->email ?? ''), ' ·') ?: '#'.$customer->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Coupon $coupon): array
    {
        $kind = $coupon->kind ?? (string) $request->input('kind');
        $percent = $request->input('discount_type') === 'percent';
        $data = $request->validate([
            'kind' => [$coupon === null ? 'required' : 'prohibited', Rule::in([Coupon::KIND_SHARED, Coupon::KIND_TEMPLATE])],
            'code' => [$coupon === null && $kind === Coupon::KIND_SHARED ? 'required' : 'prohibited', 'nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9-]+$/'],
            'prefix' => [$kind === Coupon::KIND_TEMPLATE ? 'required' : 'nullable', 'string', 'max:12', 'regex:/^[A-Za-z0-9-]+$/'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:300'],
            'discount_type' => ['required', Rule::in(['percent', 'amount'])],
            'discount_value' => ['required', 'integer', 'min:1', $percent ? 'max:'.CouponTerms::MAX_PERCENT : 'max:1000000000'],
            'max_discount' => ['nullable', 'integer', 'min:1'],
            'min_subtotal' => ['nullable', 'integer', 'min:1'],
            'scope_type' => ['required', Rule::in(CouponTerms::SCOPES)],
            'scope_values' => ['exclude_if:scope_type,all', 'required', 'array', 'min:1', 'max:200'],
            'scope_values.*' => ['integer', 'min:1', 'distinct'],
            'exclude_on_sale' => ['required', 'boolean'],
            'customer_groups' => ['nullable', 'array'],
            'customer_groups.*' => ['string', Rule::in(array_column($this->segments->groups(), 'code'))],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_per_customer' => ['nullable', 'integer', 'min:1'],
            'valid_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'starts_at' => ['nullable', 'date_format:'.StoreClock::INPUT_FORMAT],
            'ends_at' => ['nullable', 'date_format:'.StoreClock::INPUT_FORMAT],
            'active' => ['required', 'boolean'],
            'exclusive' => ['required', 'boolean'],
            'public' => ['nullable', 'boolean'],
            'lock_version' => [$coupon === null ? 'nullable' : 'required', 'integer', 'min:0'],
        ]);

        $data['kind'] = $kind;
        $data['code'] = isset($data['code']) ? strtoupper((string) $data['code']) : null;
        $data['prefix'] = isset($data['prefix']) ? strtoupper((string) $data['prefix']) : null;
        $data['starts_at'] = empty($data['starts_at']) ? null : StoreClock::fromInput((string) $data['starts_at']);
        $data['ends_at'] = empty($data['ends_at']) ? null : StoreClock::fromInput((string) $data['ends_at']);
        if ($data['starts_at'] !== null && $data['ends_at'] !== null && $data['ends_at']->lessThanOrEqualTo($data['starts_at'])) {
            throw ValidationException::withMessages(['ends_at' => __('vani-coupon-code::texts.ends_after_starts')]);
        }
        $data['public'] = (bool) ($data['public'] ?? false);
        $data['scope_values'] = array_map('intval', $data['scope_values'] ?? []);
        $data['scope_slugs'] = $this->scopeSlugs((string) $data['scope_type'], $data['scope_values']);

        return $data;
    }

    /**
     * Bộ sưu tập/thương hiệu: rule nhận slug — chỉ lấy được của đối tượng đang hiển thị.
     *
     * @param  list<int>  $ids
     * @return list<string>
     */
    private function scopeSlugs(string $scope, array $ids): array
    {
        if (! in_array($scope, ['collections', 'brands'], true)) {
            return [];
        }
        $visible = $this->catalog->visible(self::LOOKUP_KINDS[$scope], $ids, StoreLocale::default());
        if (count($visible) !== count($ids)) {
            throw ValidationException::withMessages(['scope_values' => __('vani-coupon-code::texts.scope_hidden')]);
        }

        return array_values(array_map(fn (array $row): string => $row['slug'], $visible));
    }
}
