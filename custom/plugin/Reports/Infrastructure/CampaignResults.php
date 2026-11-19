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
            ['label' => __('vani-reports::texts.campaign_results.campaign_orders'), 'value' => (string) $totals->ordersCount],
            ['label' => __('vani-reports::texts.campaign_results.promotion_uses'), 'value' => (string) $usage->usages],
            ['label' => __('vani-reports::texts.campaign_results.orders_sale_member_prices'), 'value' => (string) count($priceListOrders)],
            ['label' => __('vani-reports::texts.campaign_results.order_revenue'), 'value' => $this->money->formatAmount($totals->revenue, $currency)],
            ['label' => __('vani-reports::texts.campaign_results.discounted_promotions'), 'value' => $this->money->formatAmount($usage->promotionDiscount, $currency)],
            ['label' => __('vani-reports::texts.campaign_results.customers_cancelled_orders'), 'value' => "{$totals->customersCount} / {$totals->cancelledCount}"],
        ];
    }
}
