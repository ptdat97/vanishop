<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts;

use Modules\Promotion\Contracts\Data\CampaignUsage;
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
}
