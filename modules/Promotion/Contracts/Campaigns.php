<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

use Modules\Promotion\Contracts\Data\CampaignUsage;
use Modules\Promotion\Contracts\Data\ManagedCampaign;
use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract đọc chiến dịch: dữ liệu Promotion sở hữu để module/plugin báo cáo dựng "Kết quả chiến dịch" (kết hợp với
 * `OrderStatistics` của Ordering). Promotion không đọc số liệu đơn — tránh phụ thuộc ngược.
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface Campaigns
{
    /** Chiến dịch không tồn tại → null. */
    public function usage(int $campaignId): ?CampaignUsage;

    /**
     * Campaign do plugin quản lý (0.5.118), khoá theo (plugin, khoá của plugin): tạo/cập nhật, ghi lịch + trạng thái xuống
     * thành viên. Admin → Campaign chỉ xem, kèm liên kết sang plugin. Trả id campaign.
     */
    public function sync(string $owner, string $key, ManagedCampaign $campaign): int;

    /** Gỡ campaign của plugin; thành viên được tách ra (giữ nguyên). */
    public function remove(string $owner, string $key): void;

    /**
     * @return array<int|string, int> khoá của plugin => id campaign
     */
    public function ids(string $owner): array;
}
