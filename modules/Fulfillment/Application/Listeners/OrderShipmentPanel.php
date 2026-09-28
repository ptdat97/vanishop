<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application\Listeners;

use Modules\Fulfillment\Contracts\ShipmentReader;
use Modules\Ordering\Contracts\Data\OrderDetail;

/**
 * Panel "Giao hàng" trên trang đơn Admin (slot vani.admin.order.sidebar).
 */
final class OrderShipmentPanel
{
    public function __construct(private readonly ShipmentReader $shipments) {}

    /**
     * @return array{title: string, rows: list<array{label: string, value: string}>, link: array{label: string, url: string}}
     */
    public function __invoke(OrderDetail $order): array
    {
        $rows = [];
        foreach ($this->shipments->forOrder($order->id) as $shipment) {
            $rows[] = ['label' => $shipment->carrierLabel.($shipment->serviceCode !== null ? " · {$shipment->serviceCode}" : ''), 'value' => ($shipment->trackingNumber ?? 'chưa có mã').' · '.$shipment->status];
        }

        return [
            'title' => 'Giao hàng',
            'rows' => $rows === [] ? [['label' => 'Chưa có vận đơn', 'value' => '']] : $rows,
            'link' => ['label' => 'Xử lý vận đơn', 'url' => route('admin.fulfillment.shipments.index', ['order' => $order->number])],
        ];
    }
}
