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
        return __('vani-reports::texts.return_reasons_report.return_reasons_failed_deliveries');
    }

    public function description(): string
    {
        return __('vani-reports::texts.return_reasons_report.return_exchange_requests_reason_cod');
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
                    new Column('reason', __('vani-reports::texts.return_reasons_report.reason')), new Column('fault', __('vani-reports::texts.return_reasons_report.group')), new Column('requests', __('vani-reports::texts.return_reasons_report.requests'), 'number'), new Column('quantity', __('vani-reports::texts.return_reasons_report.quantity'), 'number'),
                    new Column('share', __('vani-reports::texts.return_reasons_report.share'), 'percent'), new Column('store_shipping_fee', __('vani-reports::texts.return_reasons_report.return_shipping_paid_store'), 'money'),
                ],
                array_map(fn (array $row): array => [
                    'reason' => $row['label'], 'fault' => $row['store_fault'] ? __('vani-reports::texts.return_reasons_report.store_s_fault') : __('vani-reports::texts.return_reasons_report.customer_side'),
                    'requests' => $row['requests'], 'quantity' => $row['quantity'],
                    'share' => $requests === 0 ? 0 : round($row['requests'] * 100 / $requests, 1),
                    'store_shipping_fee' => $row['store_shipping_fee'],
                ], $reasons),
            ),
            [
                new Metric(__('vani-reports::texts.return_reasons_report.return_exchange_requests'), $requests, 'number'),
                new Metric(__('vani-reports::texts.return_reasons_report.store_s_fault'), $requests === 0 ? 0 : round($storeFaultRequests * 100 / $requests, 1), 'percent', __('vani-reports::texts.return_reasons_report.store_fault_requests_requests', ['store_fault_requests' => $storeFaultRequests])),
                new Metric(__('vani-reports::texts.return_reasons_report.return_shipping_paid_store'), array_sum(array_column($reasons, 'store_shipping_fee')), 'money'),
                new Metric(__('vani-reports::texts.return_reasons_report.failed_cod_deliveries'), $cod['failed'], 'number', __('vani-reports::texts.return_reasons_report.cod_shipments_cod_shipments_delivered', ['cod_shipments' => $cod['cod_shipments'], 'delivered' => $cod['delivered']])),
                new Metric(__('vani-reports::texts.return_reasons_report.cod_failed_delivery_rate'), $cod['cod_shipments'] === 0 ? 0 : round($cod['failed'] * 100 / $cod['cod_shipments'], 1), 'percent'),
                new Metric(__('vani-reports::texts.return_reasons_report.uncollected_cod'), $cod['failed_cod_amount'], 'money'),
            ],
        );
    }
}
