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
        return 'Doanh thu theo kênh';
    }

    public function description(): string
    {
        return 'Số đơn và doanh thu theo kênh bán (web, app, Zalo, Admin…).';
    }

    protected function dimension(): SalesDimension
    {
        return SalesDimension::Source;
    }

    protected function groupLabel(): string
    {
        return 'Kênh';
    }

    private const CHANNELS = ['web' => 'Website', 'app' => 'Ứng dụng', 'zalo' => 'Zalo', 'admin' => 'Admin (nhân viên)', 'pos' => 'Tại cửa hàng', 'exchange' => 'Đơn đổi hàng'];

    protected function displayLabel(SalesBucket $bucket): string
    {
        return self::CHANNELS[$bucket->key] ?? parent::displayLabel($bucket);
    }
}
