<?php

declare(strict_types=1);

namespace Plugin\CouponCode\Infrastructure;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Modules\Customer\Contracts\CustomerDirectory;
use Plugin\CouponCode\Application\Coupons;
use Plugin\CouponCode\Persistence\Coupon;

/**
 * Phát mã riêng từ mã mẫu cho cả một nhóm khách và/hoặc tag, theo lô 500 khách (chạy lại an toàn: khách đã có mã bị bỏ qua).
 */
final class IssueCouponCodes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    public int $timeout = 600;

    public function __construct(
        public readonly int $couponId,
        public readonly ?int $groupId,
        public readonly ?string $tag,
    ) {}

    public function handle(Coupons $coupons, CustomerDirectory $customers): void
    {
        $coupon = Coupon::query()->find($this->couponId);
        if ($coupon === null) {
            return;
        }
        $afterId = 0;
        do {
            $ids = $customers->customerIds($this->groupId, $this->tag, $afterId, 500);
            $coupons->issue($coupon, $ids, now()->getTimestamp());
            $afterId = $ids === [] ? $afterId : $ids[count($ids) - 1];
        } while (count($ids) === 500);
    }
}
