<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Fulfillment\Application\AdminQueries;
use Modules\Fulfillment\Application\CarrierRegistry;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Contracts\InvalidCarrierEvent;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Webhook trạng thái vận đơn chung: /api/shipping/{carrier}/webhook.
 */
final class CarrierWebhookController
{
    public function __construct(private readonly AdminQueries $queries) {}

    public function __invoke(string $carrier, Request $request, CarrierRegistry $registry, FulfillmentService $fulfillment, CurrentContext $context): JsonResponse
    {
        $implementation = $registry->carrier($carrier);
        abort_if($implementation === null || ! $implementation->capabilities()->webhooks, 404);

        try {
            $event = $implementation->parseWebhook($request);
            $status = ShipmentStatus::tryFrom($event->status) ?? throw new InvalidCarrierEvent(__('fulfillment::texts.carrier_webhook_controller.status_status_not_standard', ['status' => $event->status]));
        } catch (InvalidCarrierEvent $exception) {
            Log::warning('Webhook vận chuyển không hợp lệ.', ['carrier' => $carrier, 'ip' => $request->ip(), 'reason' => $exception->getMessage()]);

            return response()->json(['error' => ['code' => 'shipping.invalid_webhook', 'message' => 'Invalid webhook.']], 400);
        }

        $applied = $context->runAs(ContextScope::system("carrier webhook {$carrier}"), function () use ($carrier, $event, $status, $fulfillment): ?bool {
            $shipment = $this->queries->byTracking($carrier, $event->trackingNumber);

            return $shipment === null ? null : $fulfillment->updateStatus($shipment->id, $status, $event->eventId, "carrier:{$carrier}", $event->description, $event->maskedPayload, occurredAt: $event->occurredAt);
        });

        return $applied === null
            ? response()->json(['error' => ['code' => 'shipping.unknown_shipment', 'message' => 'Unknown shipment.']], 404)
            : response()->json($event->acknowledgement);
    }
}
