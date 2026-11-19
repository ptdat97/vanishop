<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Ordering\Contracts\Data\SalesDimension;

final class PaymentMethodSalesReport extends BreakdownReport
{
    public function key(): string
    {
        return 'sales-by-payment-method';
    }

    public function label(): string
    {
        return __('vani-reports::texts.payment_method_sales_report.revenue_payment');
    }

    public function description(): string
    {
        return __('vani-reports::texts.payment_method_sales_report.orders_revenue_payment_method_customers');
    }

    protected function dimension(): SalesDimension
    {
        return SalesDimension::PaymentMethod;
    }

    protected function groupLabel(): string
    {
        return __('vani-reports::texts.payment_method_sales_report.method');
    }
}
