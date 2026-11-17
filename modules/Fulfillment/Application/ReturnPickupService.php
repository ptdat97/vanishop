<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Modules\Fulfillment\Contracts\Data\ReturnPickupData;
use Modules\Fulfillment\Contracts\FulfillmentRejected;
use Modules\Fulfillment\Contracts\ReturnPickup;
use Modules\Fulfillment\Contracts\ReturnPickups;
use Modules\Fulfillment\Contracts\ShippingCarrier;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Shared\Support\ExternalCalls;

/**
 * Hãng đến lấy hàng trả (EP v3 X3): dùng hãng của vận đơn đã giao gần nhất của đơn, nếu hãng hỗ trợ `ReturnPickup`.
 */
final class ReturnPickupService implements ReturnPickups
{
    public function __construct(private readonly CarrierRegistry $registry, private readonly OrderReader $orders) {}

    public function carrierLabel(int $orderId): ?string
    {
        [, $carrier] = $this->delivered($orderId);

        return $carrier?->label();
    }

    public function book(int $orderId, string $returnNumber, int $itemCount): string
    {
        [$shipment, $carrier] = $this->delivered($orderId);
        $order = $this->orders->find($orderId);
        if ($shipment === null || ! $carrier instanceof ReturnPickup || $order === null) {
            throw FulfillmentRejected::returnPickupUnavailable();
        }
        ExternalCalls::guard("fulfillment.return_pickup:{$shipment->carrier_code}");

        return $carrier->bookReturn(new ReturnPickupData($returnNumber, $order->number, (int) $shipment->location_id, $order->shippingAddress,
            ['full_name' => $order->recipient['full_name'], 'phone' => $order->recipient['phone']], $itemCount))->trackingNumber;
    }

    /**
     * @return array{0: Shipment|null, 1: ShippingCarrier|null}
     */
    private function delivered(int $orderId): array
    {
        $shipment = Shipment::query()->where('order_id', $orderId)->where('status', ShipmentStatus::Delivered)->latest('id')->first();
        $carrier = $shipment === null ? null : $this->registry->carrier($shipment->carrier_code);

        return [$shipment, $carrier instanceof ReturnPickup ? $carrier : null];
    }
}
