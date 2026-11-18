<?php

declare(strict_types=1);

namespace Plugin\Reports\Infrastructure\Reports;

use Modules\Extension\Contracts\Data\Column;
use Modules\Extension\Contracts\Data\Metric;
use Modules\Extension\Contracts\Data\ReportPeriod;
use Modules\Extension\Contracts\Data\ReportResult;
use Modules\Extension\Contracts\Data\Table;
use Modules\Extension\Contracts\ReportProvider;
use Modules\Fulfillment\Contracts\DeliveryStatistics;
use Modules\Returns\Contracts\ReturnStatistics;
use Plugin\Reports\ReportsServiceProvider;

final class ReturnReasonsReport implements ReportProvider
{
    public function __construct(
        private readonly ReturnStatistics $returns,
        private readonly DeliveryStatistics $deliveries,
    ) {}

    public function key(): string
    {
        return 'return-reasons';
    }

    public function label(): string
    {
        return 'Lý do trả & giao thất bại';
    }

    public function description(): string
    {
        return 'Yêu cầu đổi/trả theo lý do; vận đơn COD giao thất bại (đang hoàn / đã hoàn về) trong kỳ.';
    }

    public function permission(): string
    {
        return ReportsServiceProvider::PERMISSION;
    }

    public function run(ReportPeriod $period): ReportResult
    {
        $reasons = $this->returns->byReason($period->from, $period->to);
        $requests = array_sum(array_column($reasons, 'requests'));
        // L10: tách lỗi do cửa hàng (sai hàng, lỗi sản phẩm…) với lý do phía khách (đổi ý, không vừa size…).
        $storeFaultRequests = array_sum(array_column(array_filter($reasons, fn (array $row): bool => $row['store_fault']), 'requests'));
        $cod = $this->deliveries->cod($period->from, $period->to);

        return new ReportResult(
            new Table(
                [
                    new Column('reason', 'Lý do'), new Column('fault', 'Nhóm'), new Column('requests', 'Số yêu cầu', 'number'), new Column('quantity', 'Số lượng', 'number'),
                    new Column('share', 'Tỷ trọng', 'percent'), new Column('store_shipping_fee', 'Phí ship trả shop chịu', 'money'),
                ],
                array_map(fn (array $row): array => [
                    'reason' => $row['label'], 'fault' => $row['store_fault'] ? 'Lỗi do cửa hàng' : 'Phía khách',
                    'requests' => $row['requests'], 'quantity' => $row['quantity'],
                    'share' => $requests === 0 ? 0 : round($row['requests'] * 100 / $requests, 1),
                    'store_shipping_fee' => $row['store_shipping_fee'],
                ], $reasons),
            ),
            [
                new Metric('Yêu cầu đổi/trả', $requests, 'number'),
                new Metric('Lỗi do cửa hàng', $requests === 0 ? 0 : round($storeFaultRequests * 100 / $requests, 1), 'percent', "{$storeFaultRequests} yêu cầu"),
                new Metric('Phí ship trả shop chịu', array_sum(array_column($reasons, 'store_shipping_fee')), 'money'),
                new Metric('COD giao thất bại', $cod['failed'], 'number', "Trên {$cod['cod_shipments']} vận đơn COD · đã giao {$cod['delivered']}"),
                new Metric('Tỷ lệ giao thất bại COD', $cod['cod_shipments'] === 0 ? 0 : round($cod['failed'] * 100 / $cod['cod_shipments'], 1), 'percent'),
                new Metric('Tiền COD không thu được', $cod['failed_cod_amount'], 'money'),
            ],
        );
    }
}
