<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Modules\Integration\Contracts\Inbox;
use Modules\Integration\Domain\MessageStatus;

final class InboxService implements Inbox
{
    public function receive(string $system, string $externalEventId, string $messageType, array $payload): bool
    {
        return DB::table('integration_inbox')->insertOrIgnore([
            'system' => $system,
            'external_event_id' => $externalEventId,
            'message_type' => $messageType,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => MessageStatus::Received->value,
            'next_attempt_at' => now(),
            'correlation_id' => Context::get('correlation_id'),
            'received_at' => now(),
        ]) === 1;
    }
}
