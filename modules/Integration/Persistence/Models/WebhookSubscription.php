<?php

declare(strict_types=1);

namespace Modules\Integration\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $client_id
 * @property string $url
 * @property list<string> $event_types
 * @property string $secret
 * @property string $status
 * @property Carbon|null $failing_since
 * @property-read IntegrationClient $client
 */
final class WebhookSubscription extends Model
{
    public const TARGET_PREFIX = 'webhook:';

    protected $table = 'integration_webhook_subscriptions';

    protected $fillable = ['client_id', 'url', 'event_types', 'secret', 'status', 'failing_since'];

    protected $hidden = ['secret'];

    protected function casts(): array
    {
        return ['event_types' => 'array', 'secret' => 'encrypted', 'failing_since' => 'datetime'];
    }

    /**
     * @return BelongsTo<IntegrationClient, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(IntegrationClient::class, 'client_id');
    }

    public function target(): string
    {
        return self::TARGET_PREFIX.$this->id;
    }

    /**
     * `*` = mọi event; `order.*` = mọi event của aggregate order; còn lại so khớp chính xác.
     */
    public function wants(string $eventType): bool
    {
        foreach ($this->event_types as $pattern) {
            if ($pattern === '*' || $pattern === $eventType || (str_ends_with($pattern, '.*') && str_starts_with($eventType, substr($pattern, 0, -1)))) {
                return true;
            }
        }

        return false;
    }
}
