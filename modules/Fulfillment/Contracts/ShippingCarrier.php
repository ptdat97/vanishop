<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Contracts;

use Illuminate\Http\Request;
use Modules\Fulfillment\Contracts\Data\CarrierCapabilities;
use Modules\Fulfillment\Contracts\Data\CarrierEvent;
use Modules\Fulfillment\Contracts\Data\CarrierShipment;
use Modules\Fulfillment\Contracts\Data\ShipmentData;

/**
 * Extension point (tag `vani.shipping.carriers`). Core: `manual`. Plugin: GHN, GHTK, Viettel Post…
 * Phí giao ở checkout là `ShippingRateProvider` của Checkout (plugin hãng thường cài cả hai).
 * Bộ contract test: Modules\Fulfillment\Testing\ShippingCarrierContract.
 *
 * - createShipment() chạy trong job SAU commit, idempotent theo $shipment->publicId.
 * - parseWebhook() xác minh + chuẩn hoá; Core ghi nhận (khử trùng, không cho lùi trạng thái).
 */
interface ShippingCarrier
{
    public const CARRIERS_TAG = 'vani.shipping.carriers';

    public function code(): string;

    public function label(): string;

    public function capabilities(): CarrierCapabilities;

    public function createShipment(ShipmentData $shipment): CarrierShipment;

    public function cancel(ShipmentData $shipment): void;

    /**
     * @throws InvalidCarrierEvent
     */
    public function parseWebhook(Request $request): CarrierEvent;
}
