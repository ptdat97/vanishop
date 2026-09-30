<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts;

use Modules\Integration\Contracts\Data\DeliveryResult;
use Modules\Integration\Contracts\Data\InboxMessage;

/**
 * Extension point: xử lý message vào đã lưu inbox (webhook của SaaS, event của ERP…), bất đồng bộ.
 * Handler dịch message sang lời gọi Service contract của context sở hữu — không ghi bảng của module khác.
 * Phải idempotent (R18): cùng message có thể được xử lý lại khi retry/replay.
 */
interface InboundHandler
{
    public const TAG = 'vani.integration.inbound';

    public function system(): string;

    public function supports(string $messageType): bool;

    public function handle(InboxMessage $message): DeliveryResult;
}
