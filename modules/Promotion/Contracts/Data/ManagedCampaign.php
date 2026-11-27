<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

use Modules\Promotion\Contracts\Campaigns;
use Modules\Shared\Contracts\PluginApi;
use Modules\Shared\Domain\Schedule\WeeklySchedule;

/**
 * Campaign do plugin quản lý ({@see Campaigns::sync}, 0.5.118): plugin giữ nội dung (vd. chiến
 * dịch giá), Core giữ lịch chung, ghi lịch + trạng thái xuống thành viên, báo cáo. Thành viên: khuyến mãi và bảng giá (bảng
 * giá sale/thành viên của Core hoặc bảng giá do chính plugin quản lý qua `ManagedPriceLists`).
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
final readonly class ManagedCampaign
{
    public const DRAFT = 'draft';

    public const ACTIVE = 'active';

    public const STOPPED = 'stopped';

    /**
     * @param  string  $status  DRAFT | ACTIVE | STOPPED
     * @param  list<int>  $priceListIds
     * @param  list<int>  $promotionIds
     * @param  string|null  $editRoute  route Admin của plugin để sửa, nhận một tham số là khoá của plugin
     */
    public function __construct(
        public string $name,
        public string $status,
        public int $startsAt,
        public int $endsAt,
        public array $priceListIds = [],
        public array $promotionIds = [],
        public ?WeeklySchedule $recurrence = null,
        public ?string $description = null,
        public ?string $editRoute = null,
    ) {}
}
