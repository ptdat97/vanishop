<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Ordering\Contracts\Data\SalesBucket;
use Modules\Ordering\Contracts\Data\SalesDimension;

final class ChannelSalesReport extends BreakdownReport
{
    public function key(): string
    {
        return 'sales-by-channel';
    }

    public function label(): string
    {
        return __('vani-reports::texts.channel_sales_report.revenue_channel');
    }

    public function description(): string
    {
        return __('vani-reports::texts.channel_sales_report.orders_revenue_sales_channel_web');
    }

    protected function dimension(): SalesDimension
    {
        return SalesDimension::Source;
    }

    protected function groupLabel(): string
    {
        return __('vani-reports::texts.channel_sales_report.channel');
    }

    private const CHANNELS = ['web' => 'Website', 'app' => 'vani-reports::texts.channel_sales_report.app', 'zalo' => 'Zalo', 'admin' => 'vani-reports::texts.channel_sales_report.admin_staff', 'pos' => 'vani-reports::texts.channel_sales_report.store', 'exchange' => 'vani-reports::texts.channel_sales_report.exchange_orders'];

    protected function displayLabel(SalesBucket $bucket): string
    {
        return isset(self::CHANNELS[$bucket->key]) ? __(self::CHANNELS[$bucket->key]) : parent::displayLabel($bucket);
    }
}
