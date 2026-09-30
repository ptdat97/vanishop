<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Domain\MessageStatus;
use Modules\Integration\Domain\RetryPolicy;
use Modules\Integration\Persistence\Models\InboxRecord;
use Throwable;

/**
 * Xử lý message inbox bằng InboundHandler của plugin; retry/backoff/dead giống outbox.
 */
final class InboxProcessor
{
    public function __construct(
        private readonly ConnectorRegistry $registry,
        private readonly RetryPolicy $retry,
        private readonly int $processingTimeoutSeconds = 600,
    ) {}

    public function run(int $limit = 500, int $batchSize = 50): int
    {
        InboxRecord::query()
            ->where('status', MessageStatus::Processing)
            ->where('locked_at', '<', now()->subSeconds($this->processingTimeoutSeconds))
            ->update(['status' => MessageStatus::Received, 'locked_at' => null, 'next_attempt_at' => now()]);

        $processed = 0;
        while ($processed < $limit) {
            $batch = $this->claim(min($batchSize, $limit - $processed));
            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $record) {
                $this->process($record);
                $processed++;
            }
        }

        return $processed;
    }

    /**
     * @return Collection<int, InboxRecord>
     */
    private function claim(int $size): Collection
    {
        return DB::transaction(function () use ($size): Collection {
            $ids = InboxRecord::query()
                ->where('status', MessageStatus::Received)
                ->where('next_attempt_at', '<=', now())
                ->orderBy('id')
                ->limit($size)
                ->lock('for update skip locked')
                ->pluck('id');

            if ($ids->isEmpty()) {
                return new Collection;
            }

            InboxRecord::query()->whereIn('id', $ids)->update(['status' => MessageStatus::Processing, 'locked_at' => now()]);

            return InboxRecord::query()->whereIn('id', $ids)->orderBy('id')->get();
        });
    }

    private function process(InboxRecord $record): void
    {
        $previous = Context::get('correlation_id');
        Context::add('correlation_id', $record->correlation_id ?? $previous);

        try {
            $handler = $this->registry->inboundHandler($record->system, $record->message_type);

            try {
                $result = $handler === null
                    ? DeliveryResult::permanent("handler.missing:{$record->system}:{$record->message_type}")
                    : $handler->handle($record->toMessage());
            } catch (Throwable $exception) {
                report($exception);
                $result = DeliveryResult::retryable($exception::class.': '.$exception->getMessage());
            }

            $this->apply($record, $result);
        } finally {
            Context::add('correlation_id', $previous);
        }
    }

    private function apply(InboxRecord $record, DeliveryResult $result): void
    {
        $attempts = $record->attempts + 1;
        $delay = $result->isRetryable() ? $this->retry->delayAfter($attempts) : null;

        $status = match (true) {
            $result->isOk() => MessageStatus::Processed,
            $result->isStale() => MessageStatus::IgnoredStale,
            $delay !== null => MessageStatus::Received,
            $result->isRetryable() => MessageStatus::Dead,
            default => MessageStatus::Failed,
        };

        $record->update([
            'status' => $status,
            'attempts' => $attempts,
            'locked_at' => null,
            'last_error' => $result->isOk() ? null : mb_substr((string) $result->error, 0, 2000),
            'next_attempt_at' => $delay === null ? null : now()->addSeconds($delay),
            'processed_at' => in_array($status, [MessageStatus::Processed, MessageStatus::IgnoredStale], true) ? now() : null,
        ]);

        if (in_array($status, [MessageStatus::Failed, MessageStatus::Dead], true)) {
            Log::error('Xử lý message inbox thất bại.', [
                'inbox_id' => $record->id, 'system' => $record->system, 'type' => $record->message_type, 'status' => $status->value, 'error' => $result->error,
            ]);
        }
    }
}
