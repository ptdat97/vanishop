<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Widgets;

use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\Table;
use Modules\Ordering\Contracts\Data\SalesBucket;
use Modules\Ordering\Contracts\Data\SalesDimension;
use Modules\Ordering\Contracts\OrderStatistics;
use Plugin\Reports\ReportsServiceProvider;

final class TopProductsWidget implements DashboardWidget
{
    public function __construct(private readonly OrderStatistics $statistics) {}

    public function key(): string
    {
        return 'reports_top_products';
    }

    public function label(): string
    {
        return __('vani-reports::texts.top_products_widget.top_products_revenue_30_days');
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function width(): int
    {
        return 1;
    }

    public function order(): int
    {
        return 50;
    }

    public function render(): Table
    {
        $period = ReportPeriod::fromPreset('30d');
        $products = $this->statistics->breakdown(SalesDimension::Product, $period->from, $period->to, 5);

        return new Table(
            [new Column('product', __('vani-reports::texts.top_products_widget.products')), new Column('quantity', 'SL', 'number'), new Column('revenue', 'Doanh thu', 'money')],
            array_map(fn (SalesBucket $product): array => ['product' => $product->label, 'quantity' => $product->quantity, 'revenue' => $product->revenue], $products),
            $this->label(),
        );
    }
}
