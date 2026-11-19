<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Ordering\Contracts\Data\SalesDimension;

final class BrandSalesReport extends BreakdownReport
{
    public function key(): string
    {
        return 'sales-by-brand';
    }

    public function label(): string
    {
        return __('vani-reports::texts.brand_sales_report.revenue_brand');
    }

    public function description(): string
    {
        return __('vani-reports::texts.brand_sales_report.quantity_revenue_brand_order_lines');
    }

    protected function dimension(): SalesDimension
    {
        return SalesDimension::Brand;
    }

    protected function groupLabel(): string
    {
        return __('vani-reports::texts.brand_sales_report.brand');
    }

    protected function withQuantity(): bool
    {
        return true;
    }
}
