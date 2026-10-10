<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Illuminate\Support\Facades\DB;
use Modules\Fulfillment\Contracts\Data\ShipmentView;
use Modules\Fulfillment\Contracts\ShipmentReader;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Fulfillment\Persistence\Models\ShipmentLine;
use Modules\Shared\Support\StoreClock;

final class EloquentShipmentReader implements ShipmentReader
{
    public function __construct(private readonly CarrierRegistry $registry) {}

    public function forOrder(int $orderId): array
    {
        return Shipment::query()->with('lines')->where('order_id', $orderId)->orderBy('id')->get()
            ->map(fn (Shipment $shipment): ShipmentView => new ShipmentView(
                id: $shipment->id,
                publicId: $shipment->public_id,
                carrierCode: $shipment->carrier_code,
                carrierLabel: $this->registry->carrier($shipment->carrier_code)?->label() ?? $shipment->carrier_code,
                serviceCode: $shipment->service_code,
                trackingNumber: $shipment->tracking_number,
                status: $shipment->status->value,
                codAmount: $shipment->cod_amount,
                lastError: $shipment->last_error,
                lines: $shipment->lines->map(fn (ShipmentLine $line): array => ['order_line_id' => $line->order_line_id, 'variant_id' => $line->variant_id, 'quantity' => $line->quantity])->all(),
                events: DB::table('shipment_events')->where('shipment_id', $shipment->id)->orderBy('occurred_at')->orderBy('id')->get()
                    ->map(fn (object $event): array => ['status' => $event->status, 'description' => $event->description, 'at' => StoreClock::format((string) $event->occurred_at)])
                    ->all(),
                deliveredAt: $shipment->delivered_at?->toIso8601String(),
                locationId: $shipment->location_id,
            ))->all();
    }
}
