<?php

declare(strict_types=1);

namespace Modules\Integration\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Integration\Contracts\Data\OutboxMessage;
use Modules\Integration\Domain\MessageStatus;

/**
 * @property int $id
 * @property string $message_id
 * @property string|null $event_id
 * @property string $target
 * @property string $message_type
 * @property string $schema_version
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property array<string, mixed> $payload
 * @property MessageStatus $status
 * @property int $attempts
 * @property Carbon|null $next_attempt_at
 * @property Carbon|null $locked_at
 * @property string|null $correlation_id
 * @property string|null $last_error
 * @property string|null $external_id
 * @property Carbon|null $created_at
 * @property Carbon|null $sent_at
 */
final class OutboxRecord extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'integration_outbox';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array', 'status' => MessageStatus::class, 'attempts' => 'integer',
            'next_attempt_at' => 'datetime', 'locked_at' => 'datetime', 'sent_at' => 'datetime',
        ];
    }

    public function toMessage(): OutboxMessage
    {
        return new OutboxMessage(
            $this->message_id, $this->target, $this->message_type, $this->schema_version,
            $this->aggregate_type, $this->aggregate_id, $this->payload, $this->correlation_id, $this->attempts + 1,
        );
    }
}
