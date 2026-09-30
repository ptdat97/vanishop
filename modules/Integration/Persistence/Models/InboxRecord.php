<?php

declare(strict_types=1);

namespace Modules\Integration\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Integration\Contracts\Data\InboxMessage;
use Modules\Integration\Domain\MessageStatus;

/**
 * @property int $id
 * @property string $system
 * @property string $external_event_id
 * @property string $message_type
 * @property array<string, mixed> $payload
 * @property MessageStatus $status
 * @property int $attempts
 * @property Carbon|null $next_attempt_at
 * @property Carbon|null $locked_at
 * @property string|null $correlation_id
 * @property string|null $last_error
 * @property Carbon $received_at
 * @property Carbon|null $processed_at
 */
final class InboxRecord extends Model
{
    public $timestamps = false;

    protected $table = 'integration_inbox';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array', 'status' => MessageStatus::class, 'attempts' => 'integer',
            'next_attempt_at' => 'datetime', 'locked_at' => 'datetime', 'received_at' => 'datetime', 'processed_at' => 'datetime',
        ];
    }

    public function toMessage(): InboxMessage
    {
        return new InboxMessage($this->id, $this->system, $this->external_event_id, $this->message_type, $this->payload, $this->correlation_id, $this->attempts + 1);
    }
}
