<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts;

/**
 * Service contract: lưu message vào TRƯỚC khi xử lý (ADR-013). Endpoint webhook của plugin: xác thực chữ ký →
 * `receive()` → trả 2xx ngay; xử lý bất đồng bộ qua InboundHandler.
 */
interface Inbox
{
    /**
     * @param  array<string, mixed>  $payload
     * @return bool false = trùng (system, external_event_id) đã nhận trước đó — vẫn trả 2xx cho bên gửi
     */
    public function receive(string $system, string $externalEventId, string $messageType, array $payload): bool;
}
