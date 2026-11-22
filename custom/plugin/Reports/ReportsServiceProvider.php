<?php

declare(strict_types=1);

namespace Plugin\Reports;

use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\ReportProvider;
use Modules\Extension\PluginServiceProvider;
use Modules\Identity\Contracts\PermissionRegistry;
use Plugin\Reports\Infrastructure\CampaignResults;
use Plugin\Reports\Infrastructure\Reports\BrandSalesReport;
use Plugin\Reports\Infrastructure\Reports\ChannelSalesReport;
use Plugin\Reports\Infrastructure\Reports\CodAgingReport;
use Plugin\Reports\Infrastructure\Reports\DailySalesReport;
use Plugin\Reports\Infrastructure\Reports\GrossProfitByProductReport;
use Plugin\Reports\Infrastructure\Reports\GrossProfitDailyReport;
use Plugin\Reports\Infrastructure\Reports\OrderCancellationReport;
use Plugin\Reports\Infrastructure\Reports\PaymentMethodSalesReport;
use Plugin\Reports\Infrastructure\Reports\ProductSalesReport;
use Plugin\Reports\Infrastructure\Reports\ReturnReasonsReport;
use Plugin\Reports\Infrastructure\Reports\ReturnsByProductReport;
use Plugin\Reports\Infrastructure\Widgets\OrdersToProcessWidget;
use Plugin\Reports\Infrastructure\Widgets\Revenue30DaysWidget;
use Plugin\Reports\Infrastructure\Widgets\RevenueTodayWidget;
use Plugin\Reports\Infrastructure\Widgets\Sales14DaysWidget;
use Plugin\Reports\Infrastructure\Widgets\TopProductsWidget;

/**
 * Plugin báo cáo bán hàng (W6b, tham chiếu cho DashboardWidget + ReportProvider): widget Tổng quan và báo cáo
 * doanh thu theo ngày/sản phẩm/thương hiệu/thanh toán/kênh, lợi nhuận gộp (giá vốn qua hook `vani.pricing.unit_costs`), đổi trả
 * và giao thất bại COD, tab "Kết quả" của chiến dịch — đọc qua `OrderStatistics`, `ReturnStatistics`, `DeliveryStatistics`,
 * `Campaigns`; không có bảng riêng.
 */
final class ReportsServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.reports';

    public const PERMISSION = 'reports.view';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->translations($this->pluginPath('Resources/lang'), 'vani-reports');
        $this->permissions([self::PERMISSION => 'vani-reports::texts.reports_service_provider.view_sales_reports_revenue_widgets'], [PermissionRegistry::PRESET_MARKETING, PermissionRegistry::PRESET_ANALYST]);

        foreach ([RevenueTodayWidget::class, OrdersToProcessWidget::class, Revenue30DaysWidget::class, Sales14DaysWidget::class, TopProductsWidget::class] as $widget) {
            $this->contribute(DashboardWidget::TAG, $widget);
        }

        // Tab "Kết quả" trên trang chiến dịch (Promotion → Chiến dịch): ghép số liệu Promotion + Ordering (M3).
        $this->adminTab('campaign', 'results', 'vani-reports::texts.reports_service_provider.result',
            rows: fn (int $campaignId): array => $this->app->make(CampaignResults::class)->rows($campaignId), permission: self::PERMISSION);

        foreach ([DailySalesReport::class, ProductSalesReport::class, BrandSalesReport::class, PaymentMethodSalesReport::class, ChannelSalesReport::class,
            GrossProfitDailyReport::class, GrossProfitByProductReport::class, ReturnsByProductReport::class, ReturnReasonsReport::class, OrderCancellationReport::class, CodAgingReport::class] as $report) {
            $this->contribute(ReportProvider::TAG, $report);
        }
    }
}
