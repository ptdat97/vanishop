<?php

declare(strict_types=1);

namespace Modules\Integration\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Modules\Integration\Domain\MessageStatus;
use Modules\Shared\Support\ChunkedPrune;
use Modules\Tenancy\Contracts\Settings;

/**
 * Xoá log tích hợp cũ (db-refactor G3): sự kiện, outbox đã gửi, inbox đã xử lý cũ hơn `integration.retention_months`
 * (mặc định 3). Message failed/dead/pending giữ lại (còn phát lại được). Chạy hằng đêm; chạy lại an toàn.
 */
final class PruneCommand extends Command
{
    public const SETTING = 'integration.retention_months';

    protected $signature = 'vani:integration:prune';

    protected $description = 'Xoá sự kiện / outbox / inbox tích hợp đã xong và cũ hơn số tháng cấu hình';

    public function handle(Settings $settings): int
    {
        $before = now()->subMonths(max(1, (int) $settings->get('core', self::SETTING, 3)));
        $done = fn (array $statuses): \Closure => function (Builder $query) use ($statuses): void {
            $query->whereIn('status', array_map(fn (MessageStatus $status): string => $status->value, $statuses));
        };

        $outbox = ChunkedPrune::olderThan('integration_outbox', 'created_at', $before, $done([MessageStatus::Sent]));
        $inbox = ChunkedPrune::olderThan('integration_inbox', 'received_at', $before, $done([MessageStatus::Processed, MessageStatus::IgnoredStale]));
        $events = ChunkedPrune::olderThan('integration_events', 'occurred_at', $before);
        $this->info("Đã xoá {$events} sự kiện, {$outbox} outbox, {$inbox} inbox cũ hơn {$before->toDateString()}.");

        return self::SUCCESS;
    }
}
