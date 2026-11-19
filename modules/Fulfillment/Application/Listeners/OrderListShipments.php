<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;

/**
 * Danh sách đơn Admin (0.3.96): cột "Vận đơn" (slot vani.admin.order.list_columns) và thao tác hàng loạt "Tạo vận đơn",
 * "Đẩy lên hãng" (khi có hãng kết nối API — ADR-046 chốt ②), "Đã bàn giao cho hãng" (slot vani.admin.order.bulk_actions).
 * Hãng nhập tay: nhân viên tạo đơn ở hãng rồi nhập mã vận đơn ở đây.
 */
final class OrderListShipments
{
    public function __construct(private readonly CarrierRegistry $carriers) {}

    /**
     * @param  list<int>  $orderIds
     * @return array{key: string, label: string, values: array<int, string>}
     */
    public function column(array $orderIds): array
    {
        $labels = (array) __('fulfillment::labels.shipment_status');
        $values = [];
        $shipments = Shipment::query()->whereIn('order_id', $orderIds)->where('status', '!=', ShipmentStatus::Cancelled)->orderBy('id')->get();
        foreach ($shipments as $shipment) {
            $carrier = $shipment->service_code ?: ($this->carriers->carrier($shipment->carrier_code)?->label() ?? $shipment->carrier_code);
            $text = $shipment->tracking_number === null
                ? __('fulfillment::texts.order_list_shipments.no_code_yet')
                : "{$carrier} · {$shipment->tracking_number}";
            $values[$shipment->order_id] = trim(($values[$shipment->order_id] ?? '')."\n{$text} — ".($labels[$shipment->status->value] ?? $shipment->status->value));
        }

        return ['key' => 'shipments', 'label' => __('fulfillment::texts.order_list_shipments.shipments'), 'values' => $values];
    }

    /**
     * @return list<array{key: string, label: string, url: string, confirm: bool}>
     */
    public function bulkActions(): array
    {
        if (! Gate::allows('fulfillment.manage')) {
            return [];
        }

        return [
            ['key' => 'create_shipments', 'label' => __('fulfillment::texts.order_list_shipments.create_shipment'), 'url' => route('admin.fulfillment.shipments.bulk-create'), 'confirm' => true],
            ...($this->carriers->hasAutoBooking() ? [['key' => 'push', 'label' => __('fulfillment::texts.order_list_shipments.hand_carrier'), 'url' => route('admin.fulfillment.shipments.bulk-push'), 'confirm' => true]] : []),
            ['key' => 'hand_over', 'label' => __('fulfillment::texts.order_list_shipments.handed_over_carrier'), 'url' => route('admin.fulfillment.shipments.bulk-hand-over'), 'confirm' => true],
            ['key' => 'export', 'label' => __('fulfillment::texts.order_list_shipments.export_file_carrier'), 'url' => route('admin.fulfillment.shipments.export'), 'confirm' => false, 'method' => 'get'],
        ];
    }

    /**
     * Công cụ ở đầu danh sách đơn (slot vani.admin.order.list_tools): nhập mã vận đơn từ file của hãng.
     *
     * @return list<array<string, mixed>>
     */
    public function tools(): array
    {
        if (! Gate::allows('fulfillment.manage')) {
            return [];
        }

        return [[
            'key' => 'import_tracking', 'label' => __('fulfillment::texts.order_list_shipments.import_tracking_numbers_from_file'), 'url' => route('admin.fulfillment.shipments.import-tracking'),
            'accept' => '.xlsx,.csv', 'max_kb' => 5120,
            'help' => __('fulfillment::texts.order_list_shipments.excel_file_exported_carrier_filled'),
            'fields' => [['key' => 'carrier', 'label' => __('fulfillment::texts.order_list_shipments.carrier_when_file_has_no'), 'placeholder' => 'vd. GHN']],
        ]];
    }
}
