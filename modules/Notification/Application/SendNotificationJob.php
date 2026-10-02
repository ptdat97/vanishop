<?php

declare(strict_types=1);

namespace Modules\Notification\Application;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Data\SendResult;
use Modules\Notification\Persistence\Models\NotificationLog;
use Throwable;

/**
 * Gửi một dòng notification_logs. Idempotent: dòng đã `sent`/`failed`/`skipped` thì bỏ qua.
 * Lỗi tạm thời → thử lại theo backoff; hết lượt hoặc lỗi vĩnh viễn → `failed`.
 */
final class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [60, 300, 900, 3600];

    public function __construct(public readonly int $logId)
    {
        $this->onQueue('notifications');
    }

    public function handle(ChannelRegistry $channels): void
    {
        $log = NotificationLog::query()->find($this->logId);
        if ($log === null || $log->status !== NotificationLog::QUEUED) {
            return;
        }

        $channel = $channels->all()[$log->channel] ?? null;
        $attempt = $log->attempts + 1;

        try {
            $result = $channel === null
                ? SendResult::permanent("channel.unavailable:{$log->channel}")
                : $channel->send($this->message($log, $attempt));
        } catch (Throwable $exception) {
            report($exception);
            $result = SendResult::retryable($exception::class.': '.$exception->getMessage());
        }

        if ($result->isSent()) {
            $log->update(['status' => NotificationLog::SENT, 'attempts' => $attempt, 'sent_at' => now(), 'provider_message_id' => $result->providerMessageId, 'error' => null]);

            return;
        }

        $retry = $result->isRetryable() && $attempt < $this->tries;
        $log->update([
            'status' => $retry ? NotificationLog::QUEUED : NotificationLog::FAILED,
            'attempts' => $attempt,
            'error' => mb_substr((string) $result->error, 0, 500),
        ]);

        if ($retry) {
            $this->release($this->backoff[$attempt - 1] ?? end($this->backoff));

            return;
        }

        Log::warning('Gửi thông báo thất bại.', ['log_id' => $log->id, 'channel' => $log->channel, 'type' => $log->type, 'error' => $result->error]);
    }

    private function message(NotificationLog $log, int $attempt): OutgoingMessage
    {
        $recipient = $log->channel === 'mail'
            ? new Recipient(email: $log->recipient, customerId: $log->customer_id)
            : new Recipient(phone: $log->recipient, customerId: $log->customer_id);

        return new OutgoingMessage($log->id, $log->idempotency_key, $log->type, $recipient, $log->subject, $log->body, $log->meta ?? [], $attempt);
    }
}
