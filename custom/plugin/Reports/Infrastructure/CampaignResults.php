<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure;

use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Promotion\Contracts\Campaigns;
use Modules\Shared\Support\MoneyFormatter;

/**
 * Tab "Kết quả" trên trang chiến dịch: đơn dùng khuyến mãi của chiến dịch HOẶC mua theo bảng giá của chiến dịch — số đơn,
 * doanh thu, khách, đơn huỷ (Ordering) + tổng giảm và lượt dùng khuyến mãi (Promotion). Promotion không đọc số liệu đơn,
 * nên phần ghép hai nguồn nằm ở plugin báo cáo.
 */
final class CampaignResults
{
    public function __construct(
        private readonly Campaigns $campaigns,
        private readonly OrderStatistics $statistics,
        private readonly MoneyFormatter $money,
    ) {}

    /**
     * @return list<array{label: string, value: string}>
     */
    public function rows(int $campaignId): array
    {
        $usage = $this->campaigns->usage($campaignId);
        if ($usage === null) {
            return [];
        }
        $priceListOrders = $usage->priceListCodes === [] ? [] : $this->statistics->orderIdsWithPriceLists($usage->priceListCodes);
        $currency = (string) config('vanishop.currency', 'VND');
        $totals = $this->statistics->summarize(array_values(array_unique([...$usage->promotionOrderIds, ...$priceListOrders])));

        return [
            ['label' => 'Đơn của chiến dịch', 'value' => (string) $totals->ordersCount],
            ['label' => 'Lượt dùng khuyến mãi', 'value' => (string) $usage->usages],
            ['label' => 'Đơn hưởng giá sale/thành viên', 'value' => (string) count($priceListOrders)],
            ['label' => 'Doanh thu các đơn', 'value' => $this->money->formatAmount($totals->revenue, $currency)],
            ['label' => 'Đã giảm (khuyến mãi)', 'value' => $this->money->formatAmount($usage->promotionDiscount, $currency)],
            ['label' => 'Khách / đơn huỷ', 'value' => "{$totals->customersCount} / {$totals->cancelledCount}"],
        ];
    }
}
