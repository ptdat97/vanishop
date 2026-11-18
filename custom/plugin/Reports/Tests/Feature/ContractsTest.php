<?php

use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Extension\Testing\DashboardWidgetContract;
use Modules\Extension\Testing\ReportProviderContract;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\Reports\Infrastructure\Reports\BrandSalesReport;
use Plugin\Reports\Infrastructure\Reports\ChannelSalesReport;
use Plugin\Reports\Infrastructure\Reports\DailySalesReport;
use Plugin\Reports\Infrastructure\Reports\GrossProfitByProductReport;
use Plugin\Reports\Infrastructure\Reports\GrossProfitDailyReport;
use Plugin\Reports\Infrastructure\Reports\PaymentMethodSalesReport;
use Plugin\Reports\Infrastructure\Reports\ProductSalesReport;
use Plugin\Reports\Infrastructure\Reports\ReturnReasonsReport;
use Plugin\Reports\Infrastructure\Reports\ReturnsByProductReport;
use Plugin\Reports\Infrastructure\Widgets\OrdersToProcessWidget;
use Plugin\Reports\Infrastructure\Widgets\Revenue30DaysWidget;
use Plugin\Reports\Infrastructure\Widgets\RevenueTodayWidget;
use Plugin\Reports\Infrastructure\Widgets\Sales14DaysWidget;
use Plugin\Reports\Infrastructure\Widgets\TopProductsWidget;
use Plugin\Reports\ReportsServiceProvider;

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

beforeEach(function () {
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(ReportsServiceProvider::ID);
        app(PluginManager::class)->enable(ReportsServiceProvider::ID);
    });
    app()->register(ReportsServiceProvider::class);
});

foreach ([DailySalesReport::class, ProductSalesReport::class, BrandSalesReport::class, PaymentMethodSalesReport::class, ChannelSalesReport::class, GrossProfitDailyReport::class, GrossProfitByProductReport::class, ReturnsByProductReport::class, ReturnReasonsReport::class] as $report) {
    ReportProviderContract::define('vani.reports '.class_basename($report), fn () => app($report));
}
foreach ([RevenueTodayWidget::class, OrdersToProcessWidget::class, Revenue30DaysWidget::class, Sales14DaysWidget::class, TopProductsWidget::class] as $widget) {
    DashboardWidgetContract::define('vani.reports '.class_basename($widget), fn () => app($widget));
}
