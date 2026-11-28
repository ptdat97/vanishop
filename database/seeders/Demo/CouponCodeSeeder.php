<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Promotion\Persistence\Models\Voucher;
use Modules\Shared\Support\Phones;
use Plugin\CouponCode\Application\Coupons;
use Plugin\CouponCode\Persistence\Coupon;

final class CouponCodeSeeder extends Seeder
{
    public function run(Coupons $coupons): void
    {
        $now = now()->toImmutable()->utc();
        $defaults = [
            'scope_type' => 'all',
            'active' => true,
            'exclusive' => false,
            'exclude_on_sale' => false,
            'public' => true,
            'usage_per_customer' => 1,
            'starts_at' => $now->subHour(),
            'ends_at' => $now->addDays(30),
        ];

        foreach ([
            [
                'code' => 'VANIDEMO10', 'name' => '[demo] Giảm 10%', 'discount_type' => 'percent',
                'discount_value' => 10, 'max_discount' => 80_000, 'min_subtotal' => 400_000, 'usage_limit' => 100,
                'exclude_on_sale' => true,
                'description' => 'Giảm 10% tối đa 80.000 ₫ cho đơn từ 400.000 ₫. Áp dụng cho sản phẩm nguyên giá.',
            ],
            [
                'code' => 'VANIDEMO30K', 'name' => '[demo] Giảm 30.000 ₫', 'discount_type' => 'amount',
                'discount_value' => 30_000, 'min_subtotal' => 300_000, 'usage_limit' => 50,
                'description' => 'Giảm 30.000 ₫ cho đơn từ 300.000 ₫.',
            ],
        ] as $offer) {
            if (! Coupon::query()->where('code', $offer['code'])->exists() && ! Voucher::query()->where('code', $offer['code'])->exists()) {
                $coupons->save(array_replace($defaults, $offer, ['kind' => Coupon::KIND_SHARED]));
            }
        }

        $template = Coupon::query()->where('kind', Coupon::KIND_TEMPLATE)->where('name', '[demo] Mã riêng cho khách')->first();
        $template ??= $coupons->save(array_replace($defaults, [
            'kind' => Coupon::KIND_TEMPLATE, 'prefix' => 'VANIDEMOVIP', 'name' => '[demo] Mã riêng cho khách',
            'discount_type' => 'amount', 'discount_value' => 40_000, 'valid_days' => 14,
            'public' => false, 'description' => 'Giảm 40.000 ₫, dùng một lần.',
        ]));

        $customer = Customer::query()->where('phone', Phones::fromString('0912345678')->e164)->where('email', 'lan.nguyen@example.com')->first(['id']);
        if ($customer !== null && $template->active && ($template->ends_at === null || $template->ends_at->isFuture())) {
            $coupons->issue($template, [$customer->id], $now->getTimestamp());
        }
    }
}
