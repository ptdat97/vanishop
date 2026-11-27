<?php

declare(strict_types=1);

namespace Plugin\CouponCode\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Promotion\Contracts\Data\ManagedPromotion;
use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Promotion\Contracts\Vouchers;
use Modules\Shared\Support\StoreLocale;
use Plugin\CouponCode\CouponCodeServiceProvider;
use Plugin\CouponCode\Domain\CouponTerms;
use Plugin\CouponCode\Persistence\Coupon;

/**
 * Lưu mã giảm giá và đồng bộ sang Core: mỗi mã là một khuyến mãi cần nhập mã do plugin quản lý (`ManagedPromotions`),
 * mã dùng chung/mã sinh thêm/mã riêng là voucher của khuyến mãi đó (`Vouchers`).
 */
final class Coupons
{
    public const EDIT_ROUTE = 'admin.plugins.vani-coupon-code.edit';

    public function __construct(
        private readonly ManagedPromotions $promotions,
        private readonly Vouchers $vouchers,
    ) {}

    /**
     * @param  array<string, mixed>  $data  đã validate (CouponController)
     */
    public function save(array $data, ?Coupon $coupon = null, ?int $expectedLockVersion = null): Coupon
    {
        return DB::transaction(function () use ($data, $coupon, $expectedLockVersion): Coupon {
            if ($coupon !== null) {
                $updated = Coupon::query()->whereKey($coupon->id)->where('lock_version', $expectedLockVersion)->increment('lock_version');
                if ($updated === 0) {
                    throw ValidationException::withMessages(['lock_version' => __('vani-coupon-code::texts.stale')]);
                }
                $coupon->refresh();
            }
            $creating = $coupon === null;
            $coupon ??= new Coupon(['kind' => $data['kind'], 'code' => $data['code'] ?? null]);
            $translations = (array) ($coupon->translations ?? []);
            $translations[StoreLocale::default()] = ['description' => (string) ($data['description'] ?? '')];
            $coupon->fill([
                'prefix' => $data['prefix'] ?? null, 'name' => $data['name'], 'translations' => $translations,
                'discount_type' => $data['discount_type'], 'discount_value' => $data['discount_value'],
                'max_discount' => $data['discount_type'] === 'percent' ? ($data['max_discount'] ?? null) : null,
                'min_subtotal' => $data['min_subtotal'] ?? null, 'scope_type' => $data['scope_type'],
                'scope_values' => $data['scope_type'] === 'all' ? [] : array_values($data['scope_values'] ?? []),
                'scope_slugs' => $data['scope_type'] === 'all' ? [] : array_values($data['scope_slugs'] ?? []),
                'exclude_on_sale' => (bool) $data['exclude_on_sale'], 'customer_groups' => array_values($data['customer_groups'] ?? []),
                'usage_limit' => $data['usage_limit'] ?? null, 'usage_per_customer' => $data['usage_per_customer'] ?? null,
                'valid_days' => $coupon->kind === Coupon::KIND_TEMPLATE ? ($data['valid_days'] ?? null) : null,
                'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null,
                'active' => (bool) $data['active'], 'exclusive' => (bool) $data['exclusive'],
                'public' => $coupon->kind === Coupon::KIND_SHARED && (bool) $data['public'],
            ])->save();

            $coupon->promotion_id = $this->sync($coupon);
            $coupon->save();
            if ($creating && $coupon->kind === Coupon::KIND_SHARED && $coupon->code !== null) {
                $this->vouchers->create($coupon->promotion_id, $coupon->code);
            }

            return $coupon;
        });
    }

    public function delete(Coupon $coupon): void
    {
        DB::transaction(function () use ($coupon): void {
            $this->promotions->remove(CouponCodeServiceProvider::ID, (string) $coupon->id);
            $coupon->delete();
        });
    }

    /**
     * Sinh thêm mã dùng chung (vd. in phiếu): `count` mã `PREFIX` + 8 ký tự, mỗi mã `usageLimit` lượt.
     *
     * @return list<string>
     */
    public function generate(Coupon $coupon, string $prefix, int $count, ?int $usageLimit): array
    {
        return $this->vouchers->generate((int) $coupon->promotion_id, $prefix, $count, $usageLimit, $coupon->ends_at?->getTimestamp());
    }

    /**
     * Phát mã riêng từ mã mẫu: mỗi khách một mã một lượt; khách đã có mã của mã mẫu này thì bỏ qua.
     *
     * @param  list<int>  $customerIds
     * @return int số mã đã phát
     */
    public function issue(Coupon $coupon, array $customerIds, int $now): int
    {
        if ($coupon->kind !== Coupon::KIND_TEMPLATE || $coupon->promotion_id === null) {
            return 0;
        }
        $issued = [];
        foreach ($this->vouchers->ofPromotion($coupon->promotion_id) as $voucher) {
            if ($voucher->customerId !== null) {
                $issued[$voucher->customerId] = true;
            }
        }
        $expiresAt = $coupon->ends_at?->getTimestamp();
        if ($coupon->valid_days !== null) {
            $expiresAt = min($expiresAt ?? PHP_INT_MAX, $now + $coupon->valid_days * 86_400);
        }

        $count = 0;
        foreach (array_values(array_unique($customerIds)) as $customerId) {
            if (isset($issued[$customerId])) {
                continue;
            }
            $this->vouchers->generate($coupon->promotion_id, (string) $coupon->prefix, 1, 1, $expiresAt, $customerId);
            $issued[$customerId] = true;
            $count++;
        }

        return $count;
    }

    private function sync(Coupon $coupon): int
    {
        try {
            return $this->promotions->sync(CouponCodeServiceProvider::ID, (string) $coupon->id, new ManagedPromotion(
                name: __('vani-coupon-code::texts.promotion_name', ['name' => $coupon->name]),
                active: $coupon->active,
                actionType: CouponTerms::actionType($coupon),
                actionConfig: CouponTerms::actionConfig($coupon),
                rules: CouponTerms::rules($coupon),
                startsAt: $coupon->starts_at?->getTimestamp(),
                endsAt: $coupon->ends_at?->getTimestamp(),
                priority: 0,
                exclusive: $coupon->exclusive,
                usageLimit: $coupon->usage_limit,
                editRoute: self::EDIT_ROUTE,
                requiresVoucher: true,
                usageLimitPerCustomer: $coupon->usage_per_customer,
            ));
        } catch (ValidationException $invalid) {
            // Rule của `vani.promotion-rules` từ chối (plugin tắt, nhóm khách đã xoá…): báo chung trên form.
            throw ValidationException::withMessages(['business' => array_merge(...array_values($invalid->errors()))]);
        }
    }
}
