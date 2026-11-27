<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Promotion\Contracts\Vouchers;
use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Schedule\WeeklySchedule;

/**
 * Khuyến mãi do plugin quản lý ({@see ManagedPromotions}): plugin giữ nội dung (vd. combo),
 * Core giữ lịch chạy, ngân sách, lượt dùng, báo cáo và ghi nhận trên đơn. Rule/action do Core hoặc plugin đăng ký.
 */
#[PluginApi]
final readonly class ManagedPromotion
{
    /**
     * @param  array<string, mixed>  $actionConfig
     * @param  list<array{type: string, config: array<string, mixed>}>  $rules
     * @param  string|null  $editRoute  route Admin của plugin để sửa, nhận một tham số là khoá của plugin
     * @param  bool  $requiresVoucher  true = chỉ áp khi khách nhập mã (mã tạo qua {@see Vouchers}, 0.5.109)
     * @param  int|null  $usageLimitPerCustomer  lượt tối đa mỗi khách (0.5.109)
     * @param  WeeklySchedule|null  $recurrence  lịch lặp theo tuần (0.5.116)
     */
    public function __construct(
        public string $name,
        public bool $active,
        public string $actionType,
        public array $actionConfig,
        public array $rules = [],
        public ?int $startsAt = null,
        public ?int $endsAt = null,
        public int $priority = 100,
        public bool $exclusive = false,
        public ?int $usageLimit = null,
        public ?int $budgetAmount = null,
        public ?string $editRoute = null,
        public bool $requiresVoucher = false,
        public ?int $usageLimitPerCustomer = null,
        public ?WeeklySchedule $recurrence = null,
    ) {}
}
