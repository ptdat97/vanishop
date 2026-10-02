<?php

declare(strict_types=1);

namespace Modules\Integration\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Event feed (append-only).
 *
 * @property int $id
 * @property string $event_id
 * @property string $event_type
 * @property string $schema_version
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property array<string, mixed> $payload
 * @property string|null $correlation_id
 * @property Carbon $occurred_at
 */
final class IntegrationEventRecord extends Model
{
    public $timestamps = false;

    protected $table = 'integration_events';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['payload' => 'array', 'occurred_at' => 'immutable_datetime'];
    }

    /**
     * @return array<string, mixed>
     */
    public function envelope(): array
    {
        return [
            'event_id' => $this->event_id,
            'event_type' => $this->event_type,
            'schema_version' => $this->schema_version,
            'occurred_at' => $this->occurred_at->timezone('Asia/Ho_Chi_Minh')->toIso8601String(),
            'source' => 'vanishop',
            'aggregate' => ['type' => $this->aggregate_type, 'id' => $this->aggregate_id],
            'correlation_id' => $this->correlation_id,
            'data' => $this->payload,
        ];
    }
}
