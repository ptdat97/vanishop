<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Delivery;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Domain\HmacSignature;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Modules\Integration\Persistence\Models\WebhookSubscription;

/**
 * Gửi webhook cho đối tác: envelope JSON, ký HMAC, `Idempotency-Key = message_id`. Đối tác phải trả 2xx trong 10s.
 * Lỗi liên tục quá `pause_after_hours` → subscription `paused`.
 */
final class WebhookSender
{
    public function __construct(private readonly int $pauseAfterHours = 24) {}

    public function send(WebhookSubscription $subscription, OutboxRecord $record): DeliveryResult
    {
        if ($subscription->status !== 'active' || ! $subscription->client->isActive()) {
            return DeliveryResult::permanent('subscription.paused');
        }

        $body = (string) json_encode($record->payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        try {
            $response = Http::connectTimeout(3)
                ->timeout(10)
                ->withHeaders([
                    'X-Vani-Event' => $record->message_type,
                    'X-Vani-Signature' => HmacSignature::header($subscription->secret, $body, now()->getTimestamp()),
                    'Idempotency-Key' => $record->message_id,
                    'X-Correlation-Id' => (string) $record->correlation_id,
                    'User-Agent' => 'VaniShop-Webhook/1',
                ])
                ->withBody($body, 'application/json')
                ->post($subscription->url);
        } catch (ConnectionException $exception) {
            $this->recordFailure($subscription);

            return DeliveryResult::retryable('connection: '.$exception->getMessage());
        }

        if ($response->successful()) {
            if ($subscription->failing_since !== null) {
                $subscription->update(['failing_since' => null]);
            }

            return DeliveryResult::ok();
        }

        $this->recordFailure($subscription);
        $error = "http {$response->status()}";

        return in_array($response->status(), [408, 409, 425, 429], true) || $response->serverError()
            ? DeliveryResult::retryable($error)
            : DeliveryResult::permanent($error);
    }

    private function recordFailure(WebhookSubscription $subscription): void
    {
        if ($subscription->failing_since === null) {
            $subscription->update(['failing_since' => now()]);

            return;
        }

        if ($subscription->failing_since->lte(now()->subHours($this->pauseAfterHours))) {
            $subscription->update(['status' => 'paused']);
            Log::warning('Webhook subscription bị tạm dừng do lỗi liên tục.', [
                'subscription_id' => $subscription->id, 'client' => $subscription->client->code, 'failing_since' => $subscription->failing_since->toIso8601String(),
            ]);
        }
    }
}
