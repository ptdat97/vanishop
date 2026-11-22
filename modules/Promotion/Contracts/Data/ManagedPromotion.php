<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Shared\Contracts\PluginApi;

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
    ) {}
}
