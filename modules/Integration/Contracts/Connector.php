<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts;

use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Contracts\Data\HealthStatus;
use Modules\Integration\Contracts\Data\OutboxMessage;

/**
 * Extension point (mô hình B): VaniShop chủ động gửi message ra hệ thống ngoài (ERP, HĐĐT, sàn…).
 * Plugin đóng góp qua `contribute(Connector::TAG, …)`; chỉ có hiệu lực khi plugin bật.
 *
 * Mỗi event của feed được fan-out thành một message outbox cho mọi connector `supports()` loại đó;
 * worker gọi `send()` sau commit, có retry/backoff/dead letter. Connector phải gửi header
 * `Idempotency-Key: <messageId>` để phía nhận khử trùng lặp khi retry/replay.
 *
 * @see docs/11-integration/integration-platform.md §6
 */
interface Connector
{
    public const TAG = 'vani.integration.connectors';

    /** Mã hệ thống, cũng là `target` của message outbox và `system` của external_references. */
    public function system(): string;

    public function supports(string $messageType): bool;

    /**
     * Không ném exception cho lỗi dự kiến: trả `DeliveryResult::retryable()` (timeout, 5xx, 429)
     * hoặc `permanent()` (4xx do dữ liệu, mapping thiếu). Exception bất ngờ được coi là retryable.
     */
    public function send(OutboxMessage $message): DeliveryResult;

    public function healthCheck(): HealthStatus;
}
