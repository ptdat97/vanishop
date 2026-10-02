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
        return 'Doanh thu theo thanh toán';
    }

    public function description(): string
    {
        return 'Số đơn và doanh thu theo phương thức thanh toán khách chọn.';
    }

    protected function dimension(): SalesDimension
    {
        return SalesDimension::PaymentMethod;
    }

    protected function groupLabel(): string
    {
        return 'Phương thức';
    }
}
