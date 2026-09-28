<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Contracts\InvalidCarrierEvent;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Webhook trạng thái vận đơn chung: /api/shipping/{carrier}/webhook.
 */
final class CarrierWebhookController
{
    public function __invoke(string $carrier, Request $request, CarrierRegistry $registry, FulfillmentService $fulfillment, CurrentContext $context): JsonResponse
    {
        $implementation = $registry->carrier($carrier);
        abort_if($implementation === null || ! $implementation->capabilities()->webhooks, 404);

        try {
            $event = $implementation->parseWebhook($request);
            $status = ShipmentStatus::tryFrom($event->status) ?? throw new InvalidCarrierEvent("Trạng thái [{$event->status}] không chuẩn.");
        } catch (InvalidCarrierEvent $exception) {
            Log::warning('Webhook vận chuyển không hợp lệ.', ['carrier' => $carrier, 'ip' => $request->ip(), 'reason' => $exception->getMessage()]);

            return response()->json(['error' => ['code' => 'shipping.invalid_webhook', 'message' => 'Invalid webhook.']], 400);
        }

        $applied = $context->runAs(ContextScope::system("carrier webhook {$carrier}"), function () use ($carrier, $event, $status, $fulfillment): ?bool {
            $shipment = Shipment::query()->where('carrier_code', $carrier)->where('tracking_number', $event->trackingNumber)->first();

            return $shipment === null ? null : $fulfillment->updateStatus($shipment->id, $status, $event->eventId, "carrier:{$carrier}", $event->description, $event->maskedPayload, occurredAt: $event->occurredAt);
        });

        return $applied === null
            ? response()->json(['error' => ['code' => 'shipping.unknown_shipment', 'message' => 'Unknown shipment.']], 404)
            : response()->json($event->acknowledgement);
    }
}
