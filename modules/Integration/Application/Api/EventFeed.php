<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Api;

use Modules\Integration\Persistence\Models\IntegrationClient;
use Modules\Integration\Persistence\Models\IntegrationEventRecord;

/**
 * GET /events?after=<cursor>: thay cho webhook khi đối tác muốn kéo, hoặc để bắt kịp sau khi subscription bị dừng.
 */
final class EventFeed
{
    /**
     * @return array{data: list<array<string, mixed>>, next_cursor: int, has_more: bool}
     */
    public function page(IntegrationClient $client, int $after, int $limit, ?string $type = null): array
    {
        $rows = IntegrationEventRecord::query()
            ->where('id', '>', $after)
            ->when($type !== null && $type !== '', fn ($query) => $query->where('event_type', $type))
            ->orderBy('id')
            ->limit($limit + 1)
            ->get();

        $hasMore = $rows->count() > $limit;
        $rows = $rows->take($limit);

        return [
            'data' => $rows->map(fn (IntegrationEventRecord $row): array => ['cursor' => $row->id, ...$row->envelope()])->values()->all(),
            'next_cursor' => $rows->last()->id ?? $after,
            'has_more' => $hasMore,
        ];
    }
}
