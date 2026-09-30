<?php

declare(strict_types=1);

namespace Modules\Integration\Tests\Feature\Fixtures;

use Modules\Integration\Contracts\Connector;
use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Contracts\Data\HealthStatus;
use Modules\Integration\Contracts\Data\OutboxMessage;
use RuntimeException;

/**
 * Connector giả: ghi lại message đã gửi; kết quả lấy lần lượt từ $results (hết thì ok).
 */
final class FakeErpConnector implements Connector
{
    /** @var list<OutboxMessage> */
    public static array $sent = [];

    /** @var list<DeliveryResult|RuntimeException> */
    public static array $results = [];

    /** Ghi mỗi lần gửi vào file (concurrency test nhiều tiến trình). */
    public static ?string $logFile = null;

    public static function reset(): void
    {
        self::$sent = [];
        self::$results = [];
    }

    public function system(): string
    {
        return 'fake-erp';
    }

    public function supports(string $messageType): bool
    {
        return str_starts_with($messageType, 'order.');
    }

    public function send(OutboxMessage $message): DeliveryResult
    {
        self::$sent[] = $message;
        if (self::$logFile !== null) {
            file_put_contents(self::$logFile, "{$message->messageId} {$message->aggregateId} {$message->messageType}".PHP_EOL, FILE_APPEND | LOCK_EX);
        }
        $result = array_shift(self::$results) ?? DeliveryResult::ok('ERP-'.$message->aggregateId);
        if ($result instanceof RuntimeException) {
            throw $result;
        }

        return $result;
    }

    public function healthCheck(): HealthStatus
    {
        return HealthStatus::healthy();
    }
}
