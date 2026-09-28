<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application\Carriers;

use Illuminate\Http\Request;
use Modules\Fulfillment\Contracts\Data\CarrierCapabilities;
use Modules\Fulfillment\Contracts\Data\CarrierEvent;
use Modules\Fulfillment\Contracts\Data\CarrierShipment;
use Modules\Fulfillment\Contracts\Data\ShipmentData;
use Modules\Fulfillment\Contracts\InvalidCarrierEvent;
use Modules\Fulfillment\Contracts\ShippingCarrier;

/**
 * Vận đơn thủ công: nhân viên đặt ở hãng bất kỳ (hoặc tự giao), nhập mã vận đơn và cập nhật trạng thái trong Admin.
 */
final class ManualCarrier implements ShippingCarrier
{
    public function code(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return __('fulfillment::messages.manual');
    }

    public function capabilities(): CarrierCapabilities
    {
        return new CarrierCapabilities;
    }

    public function createShipment(ShipmentData $shipment): CarrierShipment
    {
        // Mã do nhân viên nhập; không có thì dùng mã shipment nội bộ.
        return new CarrierShipment($shipment->trackingNumber ?? $shipment->publicId, null, $shipment->serviceCode);
    }

    public function cancel(ShipmentData $shipment): void {}

    public function parseWebhook(Request $request): CarrierEvent
    {
        throw new InvalidCarrierEvent('Vận đơn thủ công không có webhook.');
    }
}
