<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Integration\Domain\MessageStatus;
use Modules\Integration\Persistence\Models\InboxRecord;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Modules\Shared\Contracts\Metrics;

/**
 * Phát lại message `failed`/`dead`. Giữ nguyên message_id (outbox) / external_event_id (inbox) để phía nhận
 * vẫn khử trùng lặp được. Mỗi message được replay có một dòng audit.
 */
final class ReplayService
{
    public const MAX_PER_CALL = 1000;

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly Metrics $metrics,
    ) {}

    /**
     * @param  array{ids?: list<int>, status?: string, target?: string, since?: \DateTimeInterface|null}  $filter
     */
    public function replayOutbox(array $filter): int
    {
        $count = $this->replay(OutboxRecord::query(), $filter, 'target', MessageStatus::Pending, 'outbox');
        $this->metrics->increment('integration.event_replay', $count, 'outbox');

        return $count;
    }

    /**
     * @param  array{ids?: list<int>, status?: string, target?: string, since?: \DateTimeInterface|null}  $filter  target = system
     */
    public function replayInbox(array $filter): int
    {
        $count = $this->replay(InboxRecord::query(), $filter, 'system', MessageStatus::Received, 'inbox');
        $this->metrics->increment('integration.event_replay', $count, 'inbox');

        return $count;
    }

    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @param  array{ids?: list<int>, status?: string, target?: string, since?: \DateTimeInterface|null}  $filter
     */
    private function replay(Builder $query, array $filter, string $targetColumn, MessageStatus $restart, string $box): int
    {
        $statuses = isset($filter['status']) && $filter['status'] !== ''
            ? [MessageStatus::from($filter['status'])]
            : [MessageStatus::Failed, MessageStatus::Dead];
        $statuses = array_values(array_filter($statuses, fn (MessageStatus $status): bool => $status->isReplayable()));
        if ($statuses === []) {
            return 0;
        }

        $timestampColumn = $box === 'outbox' ? 'created_at' : 'received_at';

        return DB::transaction(function () use ($query, $filter, $targetColumn, $restart, $box, $statuses, $timestampColumn): int {
            $records = $query
                ->whereIn('status', array_map(fn (MessageStatus $status): string => $status->value, $statuses))
                ->when(isset($filter['ids']), fn ($query) => $query->whereIn('id', $filter['ids'] ?? []))
                ->when(($filter['target'] ?? '') !== '', fn ($query) => $query->where($targetColumn, $filter['target']))
                ->when(isset($filter['since']), fn ($query) => $query->where($timestampColumn, '>=', $filter['since']))
                ->orderBy('id')
                ->limit(self::MAX_PER_CALL)
                ->lockForUpdate()
                ->get();

            foreach ($records as $record) {
                $this->audit->record("integration.{$box}.replayed", "integration_{$box}", $record->getKey(), [
                    'from_status' => $record->getAttribute('status')->value,
                    'attempts' => $record->getAttribute('attempts'),
                    'last_error' => $record->getAttribute('last_error'),
                ]);
                $record->forceFill(['status' => $restart, 'attempts' => 0, 'next_attempt_at' => now(), 'locked_at' => null])->save();
            }

            return $records->count();
        });
    }
}
