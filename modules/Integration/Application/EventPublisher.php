<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Integration\Contracts\Data\IntegrationEvent;
use Modules\Integration\Contracts\IntegrationEvents;
use Modules\Integration\Domain\MessageStatus;
use Modules\Integration\Persistence\Models\IntegrationEventRecord;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Modules\Integration\Persistence\Models\WebhookSubscription;

/**
 * Ghi event feed + fan-out outbox trong MỘT transaction: có event thì chắc chắn có message cho mọi bên đăng ký.
 *
 * Subscription đang `paused` không nhận message mới — đối tác bắt kịp qua `GET /events?after=` rồi
 * được bật lại.
 */
final class EventPublisher implements IntegrationEvents
{
    public function __construct(private readonly ConnectorRegistry $registry) {}

    public function publish(IntegrationEvent $event): string
    {
        return DB::transaction(function () use ($event): string {
            $record = IntegrationEventRecord::query()->create([
                'event_id' => (string) Str::uuid(),
                'event_type' => $event->type,
                'schema_version' => $event->schemaVersion,
                'aggregate_type' => $event->aggregateType,
                'aggregate_id' => $event->aggregateId,
                'payload' => $event->data,
                'correlation_id' => Context::get('correlation_id'),
                'occurred_at' => now(),
            ]);

            $targets = [];
            foreach ($this->subscriptions($event) as $subscription) {
                $targets[] = $subscription->target();
            }
            foreach ($this->registry->connectors() as $connector) {
                if ($connector->supports($event->type)) {
                    $targets[] = $connector->system();
                }
            }

            $envelope = $record->envelope();
            foreach (array_unique($targets) as $target) {
                OutboxRecord::query()->create([
                    'message_id' => (string) Str::uuid(),
                    'event_id' => $record->event_id,
                    'target' => $target,
                    'message_type' => $event->type,
                    'schema_version' => $event->schemaVersion,
                    'aggregate_type' => $event->aggregateType,
                    'aggregate_id' => $event->aggregateId,
                    'payload' => $envelope,
                    'status' => MessageStatus::Pending,
                    'next_attempt_at' => now(),
                    'correlation_id' => $record->correlation_id,
                ]);
            }

            return $record->event_id;
        });
    }

    /**
     * @return list<WebhookSubscription>
     */
    private function subscriptions(IntegrationEvent $event): array
    {
        return WebhookSubscription::query()
            ->with('client')
            ->where('status', 'active')
            ->get()
            ->filter(fn (WebhookSubscription $subscription): bool => $subscription->client->isActive()
                && $subscription->wants($event->type))
            ->values()
            ->all();
    }
}
