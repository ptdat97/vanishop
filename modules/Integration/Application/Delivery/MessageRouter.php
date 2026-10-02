<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Delivery;

use Modules\Integration\Application\ConnectorRegistry;
use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Modules\Integration\Persistence\Models\WebhookSubscription;
use Throwable;

/**
 * `webhook:<id>` → WebhookSender; mã khác → Connector của plugin.
 */
final class MessageRouter
{
    public function __construct(
        private readonly WebhookSender $webhooks,
        private readonly ConnectorRegistry $registry,
        private readonly CircuitBreaker $breaker,
    ) {}

    /**
     * @throws CircuitOpen connector đang ngắt mạch — worker hoãn message, không tính lượt thử
     */
    public function deliver(OutboxRecord $record): DeliveryResult
    {
        if (str_starts_with($record->target, WebhookSubscription::TARGET_PREFIX)) {
            $subscription = WebhookSubscription::query()->with('client')->find((int) substr($record->target, strlen(WebhookSubscription::TARGET_PREFIX)));

            return $subscription === null
                ? DeliveryResult::permanent('subscription.not_found')
                : $this->webhooks->send($subscription, $record);
        }

        $openUntil = $this->breaker->openUntil($record->target);
        if ($openUntil !== null) {
            throw new CircuitOpen($record->target, $openUntil);
        }

        $connector = $this->registry->connector($record->target);
        if ($connector === null) {
            return DeliveryResult::permanent("connector.unavailable:{$record->target}");
        }

        try {
            $result = $connector->send($record->toMessage());
        } catch (Throwable $exception) {
            $this->breaker->recordFailure($record->target);
            throw $exception;
        }

        // Lỗi vĩnh viễn là lỗi dữ liệu của từng message, không phải dấu hiệu hệ thống ngoài đang hỏng.
        match (true) {
            $result->isRetryable() => $this->breaker->recordFailure($record->target),
            $result->isOk() => $this->breaker->recordSuccess($record->target),
            default => null,
        };

        return $result;
    }
}
