<?php

declare(strict_types=1);

namespace Modules\Notification\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $idempotency_key
 * @property string $type
 * @property string $category
 * @property string $channel
 * @property int|null $template_id
 * @property int|null $customer_id
 * @property string $recipient
 * @property string|null $subject
 * @property string|null $body
 * @property array<string, mixed>|null $meta
 * @property string $status
 * @property int $attempts
 * @property string|null $provider_message_id
 * @property string|null $error
 * @property string|null $correlation_id
 * @property Carbon $created_at
 * @property Carbon|null $sent_at
 */
final class NotificationLog extends Model
{
    public const UPDATED_AT = null;

    public const QUEUED = 'queued';

    public const SENT = 'sent';

    public const FAILED = 'failed';

    public const SKIPPED = 'skipped';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['customer_id' => 'integer', 'meta' => 'array', 'attempts' => 'integer', 'sent_at' => 'datetime'];
    }
}
