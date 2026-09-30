<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Integration\Application\Delivery\CircuitOpen;
use Modules\Integration\Application\Delivery\MessageRouter;
use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Domain\MessageStatus;
use Modules\Integration\Domain\RetryPolicy;
use Modules\Integration\Persistence\Models\OutboxRecord;
use Throwable;

/**
 * Gửi message outbox (ADR-013). Nhiều worker chạy song song an toàn:
 *
 * - Lấy message bằng `FOR UPDATE SKIP LOCKED` rồi đánh dấu `processing` trong cùng transaction.
 * - Thứ tự theo (target, aggregate): chỉ lấy message đầu hàng — message sau chờ khi message trước còn
 *   `pending`/`processing` (đang retry). Message `failed`/`dead` không chặn hàng đợi; replay khi đã sửa.
 * - `processing` quá hạn (worker chết giữa chừng) được trả về `pending` → at-least-once; phía nhận khử
 *   trùng lặp bằng `Idempotency-Key = message_id`.
 */
final class OutboxWorker
{
    public function __construct(
        private readonly MessageRouter $router,
        private readonly RetryPolicy $retry,
        private readonly int $processingTimeoutSeconds = 600,
    ) {}

    /**
     * @return int số message đã xử lý (thành công hay thất bại)
     */
    public function run(int $limit = 500, int $batchSize = 50): int
    {
        $this->reclaimStuck();

        $processed = 0;
        while ($processed < $limit) {
            $batch = $this->claim(min($batchSize, $limit - $processed));
            if ($batch->isEmpty()) {
                break;
            }

            foreach ($batch as $record) {
                $this->deliver($record);
                $processed++;
            }
        }

        return $processed;
    }

    public function reclaimStuck(): int
    {
        return OutboxRecord::query()
            ->where('status', MessageStatus::Processing)
            ->where('locked_at', '<', now()->subSeconds($this->processingTimeoutSeconds))
            ->update(['status' => MessageStatus::Pending, 'locked_at' => null, 'next_attempt_at' => now()]);
    }

    /**
     * @return Collection<int, OutboxRecord>
     */
    private function claim(int $size): Collection
    {
        return DB::transaction(function () use ($size): Collection {
            $ids = OutboxRecord::query()
                ->where('status', MessageStatus::Pending)
                ->where('next_attempt_at', '<=', now())
                ->whereNotExists(fn (Builder $query) => $query
                    ->from('integration_outbox as earlier')
                    ->whereColumn('earlier.target', 'integration_outbox.target')
                    ->whereColumn('earlier.aggregate_type', 'integration_outbox.aggregate_type')
                    ->whereColumn('earlier.aggregate_id', 'integration_outbox.aggregate_id')
                    ->whereColumn('earlier.id', '<', 'integration_outbox.id')
                    ->whereIn('earlier.status', [MessageStatus::Pending->value, MessageStatus::Processing->value]))
                ->orderBy('id')
                ->limit($size)
                ->lock('for update skip locked')
                ->pluck('id');

            if ($ids->isEmpty()) {
                return new Collection;
            }

            OutboxRecord::query()->whereIn('id', $ids)->update(['status' => MessageStatus::Processing, 'locked_at' => now()]);

            return OutboxRecord::query()->whereIn('id', $ids)->orderBy('id')->get();
        });
    }

    private function deliver(OutboxRecord $record): void
    {
        $previous = Context::get('correlation_id');
        Context::add('correlation_id', $record->correlation_id ?? $previous);

        try {
            try {
                $result = $this->router->deliver($record);
            } catch (CircuitOpen $open) {
                $record->update([
                    'status' => MessageStatus::Pending, 'locked_at' => null, 'next_attempt_at' => $open->retryAt, 'last_error' => $open->getMessage(),
                ]);

                return;
            } catch (Throwable $exception) {
                report($exception);
                $result = DeliveryResult::retryable($exception::class.': '.$exception->getMessage());
            }

            $this->apply($record, $result);
        } finally {
            Context::add('correlation_id', $previous);
        }
    }

    private function apply(OutboxRecord $record, DeliveryResult $result): void
    {
        $attempts = $record->attempts + 1;
        $context = ['message_id' => $record->message_id, 'target' => $record->target, 'type' => $record->message_type, 'attempt' => $attempts];

        if ($result->isOk()) {
            $record->update([
                'status' => MessageStatus::Sent, 'attempts' => $attempts, 'sent_at' => now(), 'locked_at' => null,
                'last_error' => null, 'external_id' => $result->externalId,
            ]);

            return;
        }

        $delay = $result->isRetryable() ? $this->retry->delayAfter($attempts) : null;
        $status = match (true) {
            $delay !== null => MessageStatus::Pending,
            $result->isRetryable() => MessageStatus::Dead,
            default => MessageStatus::Failed,
        };

        $record->update([
            'status' => $status, 'attempts' => $attempts, 'locked_at' => null, 'last_error' => mb_substr((string) $result->error, 0, 2000),
            'next_attempt_at' => $delay === null ? null : now()->addSeconds($delay),
        ]);

        $level = $status === MessageStatus::Pending ? 'info' : 'error';
        Log::log($level, 'Gửi message tích hợp thất bại.', [...$context, 'status' => $status->value, 'error' => $result->error]);
    }
}
