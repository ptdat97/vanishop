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
        return 'Doanh thu theo thương hiệu';
    }

    public function description(): string
    {
        return 'Số lượng và doanh thu theo thương hiệu của dòng đơn.';
    }

    protected function dimension(): SalesDimension
    {
        return SalesDimension::Brand;
    }

    protected function groupLabel(): string
    {
        return 'Thương hiệu';
    }

    protected function withQuantity(): bool
    {
        return true;
    }
}
