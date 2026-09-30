<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Illuminate\Support\Facades\DB;
use Modules\Integration\Domain\MessageStatus;
use Modules\Integration\Domain\PayloadMasker;
use Modules\Integration\Persistence\Models\InboxRecord;
use Modules\Integration\Persistence\Models\IntegrationClient;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Modules\Integration\Persistence\Models\WebhookSubscription;

/**
 * Dữ liệu màn hình vận hành "Integration Health" (payload đã che PII).
 */
final class HealthQueries
{
    public function __construct(private readonly ConnectorRegistry $registry) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        $oldestPending = OutboxRecord::query()->where('status', MessageStatus::Pending)->min('created_at');

        return [
            'outbox' => $this->countByStatus('integration_outbox'),
            'inbox' => $this->countByStatus('integration_inbox'),
            'oldest_pending_minutes' => $oldestPending === null ? null : (int) now()->diffInMinutes($oldestPending, true),
            'by_target' => OutboxRecord::query()
                ->whereIn('status', [MessageStatus::Pending, MessageStatus::Failed, MessageStatus::Dead])
                ->groupBy('target', 'status')
                ->orderBy('target')
                ->get(['target', 'status', DB::raw('count(*) as total')])
                ->map(fn (OutboxRecord $row): array => ['target' => $row->target, 'status' => $row->status->value, 'total' => (int) $row->getAttribute('total')])
                ->all(),
            'connectors' => array_map(fn ($connector): string => $connector->system(), $this->registry->connectors(null)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function messages(string $box, ?string $status, ?string $target): array
    {
        $statuses = $status !== null && $status !== '' ? [$status] : [MessageStatus::Failed->value, MessageStatus::Dead->value];

        if ($box === 'inbox') {
            return InboxRecord::query()->whereIn('status', $statuses)
                ->when($target !== null && $target !== '', fn ($query) => $query->where('system', $target))
                ->orderByDesc('id')->limit(100)->get()
                ->map(fn (InboxRecord $record): array => [
                    'id' => $record->id, 'target' => $record->system, 'type' => $record->message_type, 'reference' => $record->external_event_id,
                    'status' => $record->status->value, 'attempts' => $record->attempts, 'last_error' => $record->last_error,
                    'at' => $record->received_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                    'payload' => PayloadMasker::mask($record->payload), 'correlation_id' => $record->correlation_id,
                ])->all();
        }

        return OutboxRecord::query()->whereIn('status', $statuses)
            ->when($target !== null && $target !== '', fn ($query) => $query->where('target', $target))
            ->orderByDesc('id')->limit(100)->get()
            ->map(fn (OutboxRecord $record): array => [
                'id' => $record->id, 'target' => $record->target, 'type' => $record->message_type, 'reference' => "{$record->aggregate_type}:{$record->aggregate_id}",
                'status' => $record->status->value, 'attempts' => $record->attempts, 'last_error' => $record->last_error,
                'at' => $record->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                'payload' => PayloadMasker::mask($record->payload), 'correlation_id' => $record->correlation_id,
            ])->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function clients(): array
    {
        return IntegrationClient::query()->with(['keys', 'subscriptions'])->orderBy('code')->get()
            ->map(fn (IntegrationClient $client): array => [
                'id' => $client->id, 'code' => $client->code, 'name' => $client->name, 'status' => $client->status,
                'scopes' => $client->scopes, 'brand_ids' => $client->brand_ids, 'rate_limit' => $client->rate_limit,
                'active_keys' => $client->keys->filter->isUsable()->count(),
                'subscriptions' => $client->subscriptions->map(fn (WebhookSubscription $subscription): array => [
                    'id' => $subscription->id, 'url' => $subscription->url, 'event_types' => $subscription->event_types,
                    'status' => $subscription->status,
                    'failing_since' => $subscription->failing_since?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                ])->all(),
            ])->all();
    }

    /**
     * @return array<string, int>
     */
    private function countByStatus(string $table): array
    {
        return DB::table($table)->groupBy('status')->pluck(DB::raw('count(*)'), 'status')->map(fn (mixed $total): int => (int) $total)->all();
    }
}
