<?php

declare(strict_types=1);

namespace Plugin\Reports;

use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\ReportProvider;
use Modules\Extension\PluginServiceProvider;
use Plugin\Reports\Infrastructure\Reports\BrandSalesReport;
use Plugin\Reports\Infrastructure\Reports\ChannelSalesReport;
use Plugin\Reports\Infrastructure\Reports\DailySalesReport;
use Plugin\Reports\Infrastructure\Reports\PaymentMethodSalesReport;
use Plugin\Reports\Infrastructure\Reports\ProductSalesReport;
use Plugin\Reports\Infrastructure\Widgets\OrdersToProcessWidget;
use Plugin\Reports\Infrastructure\Widgets\Revenue30DaysWidget;
use Plugin\Reports\Infrastructure\Widgets\RevenueTodayWidget;
use Plugin\Reports\Infrastructure\Widgets\Sales14DaysWidget;
use Plugin\Reports\Infrastructure\Widgets\TopProductsWidget;

/**
 * Plugin báo cáo bán hàng (W6b, tham chiếu cho DashboardWidget + ReportProvider): widget Tổng quan và báo cáo
 * doanh thu theo ngày/sản phẩm/thương hiệu/thanh toán/kênh — đọc qua `OrderStatistics`, không có bảng riêng.
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
        $this->permissions([self::PERMISSION => 'Xem báo cáo bán hàng và widget doanh thu']);

        foreach ([RevenueTodayWidget::class, OrdersToProcessWidget::class, Revenue30DaysWidget::class, Sales14DaysWidget::class, TopProductsWidget::class] as $widget) {
            $this->contribute(DashboardWidget::TAG, $widget);
        }

        foreach ([DailySalesReport::class, ProductSalesReport::class, BrandSalesReport::class, PaymentMethodSalesReport::class, ChannelSalesReport::class] as $report) {
            $this->contribute(ReportProvider::TAG, $report);
        }
    }
}
