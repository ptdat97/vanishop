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
        return 'Sản phẩm bán chạy';
    }

    public function description(): string
    {
        return 'Số lượng và doanh thu theo sản phẩm (tên tại thời điểm đặt hàng).';
    }

    protected function dimension(): SalesDimension
    {
        return SalesDimension::Product;
    }

    protected function groupLabel(): string
    {
        return 'Sản phẩm';
    }

    protected function withQuantity(): bool
    {
        return true;
    }
}
