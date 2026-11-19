<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Ordering\Contracts\Data\SalesDimension;

final class ProductSalesReport extends BreakdownReport
{
    public function key(): string
    {
        return 'sales-by-product';
    }

    public function label(): string
    {
        return __('vani-reports::texts.product_sales_report.best_selling_products');
    }

    public function description(): string
    {
        return __('vani-reports::texts.product_sales_report.quantity_revenue_product_name_time');
    }

    protected function dimension(): SalesDimension
    {
        return SalesDimension::Product;
    }

    protected function groupLabel(): string
    {
        return __('vani-reports::texts.product_sales_report.products');
    }

    protected function withQuantity(): bool
    {
        return true;
    }
}
