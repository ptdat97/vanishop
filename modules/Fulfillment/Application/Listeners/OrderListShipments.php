<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application\Listeners;

use Illuminate\Support\Facades\Gate;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;

/**
 * Danh sách đơn Admin (0.3.96): cột "Vận đơn" (slot vani.admin.order.list_columns) và thao tác hàng loạt "Tạo vận đơn",
 * "Đã bàn giao cho hãng" (slot vani.admin.order.bulk_actions). Nhân viên nhập mã vận đơn lên hãng rồi ghi lại ở đây.
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
                ? 'Chưa có mã'
                : "{$carrier} · {$shipment->tracking_number}";
            $values[$shipment->order_id] = trim(($values[$shipment->order_id] ?? '')."\n{$text} — ".($labels[$shipment->status->value] ?? $shipment->status->value));
        }

        return ['key' => 'shipments', 'label' => 'Vận đơn', 'values' => $values];
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
            ['key' => 'create_shipments', 'label' => 'Tạo vận đơn', 'url' => route('admin.fulfillment.shipments.bulk-create'), 'confirm' => true],
            ['key' => 'hand_over', 'label' => 'Đã bàn giao cho hãng', 'url' => route('admin.fulfillment.shipments.bulk-hand-over'), 'confirm' => true],
            ['key' => 'export', 'label' => 'Xuất file cho hãng', 'url' => route('admin.fulfillment.shipments.export'), 'confirm' => false, 'method' => 'get'],
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
            'key' => 'import_tracking', 'label' => 'Nhập mã vận đơn từ file', 'url' => route('admin.fulfillment.shipments.import-tracking'),
            'accept' => '.xlsx,.csv', 'max_kb' => 5120,
            'help' => 'File Excel hãng xuất (hoặc file "Xuất file cho hãng" đã điền mã): cần cột số đơn của shop và cột mã vận đơn; cột hãng nếu có.',
            'fields' => [['key' => 'carrier', 'label' => 'Hãng (khi file không có cột hãng)', 'placeholder' => 'vd. GHN']],
        ]];
    }
}
