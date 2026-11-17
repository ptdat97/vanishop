<?php

declare(strict_types=1);

namespace Modules\Promotion\Contracts\Data;

/**
 * Số liệu của Promotion về một chiến dịch: đơn đã áp khuyến mãi của chiến dịch, tổng giảm, số lượt, và mã các bảng giá
 * sale/thành viên thuộc chiến dịch (để tra đơn mua theo giá sale qua `OrderStatistics::orderIdsWithPriceLists`).
 */
final readonly class CampaignUsage
{
    /**
     * @param  list<int>  $promotionOrderIds
     * @param  list<string>  $priceListCodes
     */
    public function __construct(
        public int $campaignId,
        public array $promotionOrderIds,
        public int $promotionDiscount,
        public int $usages,
        public array $priceListCodes,
    ) {}
}
