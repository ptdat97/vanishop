<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Delivery;

use Modules\Integration\Application\ConnectorRegistry;
use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Modules\Integration\Persistence\Models\WebhookSubscription;

/**
 * `webhook:<id>` → WebhookSender; mã khác → Connector của plugin (trong phạm vi brand của message).
 */
final class MessageRouter
{
    public function __construct(
        private readonly WebhookSender $webhooks,
        private readonly ConnectorRegistry $registry,
    ) {}

    public function deliver(OutboxRecord $record): DeliveryResult
    {
        if (str_starts_with($record->target, WebhookSubscription::TARGET_PREFIX)) {
            $subscription = WebhookSubscription::query()->with('client')->find((int) substr($record->target, strlen(WebhookSubscription::TARGET_PREFIX)));

            return $subscription === null
                ? DeliveryResult::permanent('subscription.not_found')
                : $this->webhooks->send($subscription, $record);
        }

        return $this->registry->inBrand($record->brand_id, function () use ($record): DeliveryResult {
            $connector = $this->registry->connector($record->target, $record->brand_id);

            return $connector === null
                ? DeliveryResult::permanent("connector.unavailable:{$record->target}")
                : $connector->send($record->toMessage());
        });
    }
}
