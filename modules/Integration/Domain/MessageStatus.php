<?php

declare(strict_types=1);

namespace Modules\Integration\Domain;

/**
 * Trạng thái message outbox/inbox.
 *
 * - pending: chờ gửi (lần đầu hoặc đã hẹn retry theo next_attempt_at)
 * - processing: một worker đang giữ
 * - sent/processed: xong
 * - failed: lỗi vĩnh viễn (dữ liệu sai, mapping thiếu…) — không tự retry, chờ sửa rồi replay
 * - dead: hết lượt retry — chờ replay
 * - ignored_stale: (inbox) bản cũ hơn dữ liệu hiện có, bỏ qua
 */
enum MessageStatus: string
{
    case Pending = 'pending';
    case Received = 'received';
    case Processing = 'processing';
    case Sent = 'sent';
    case Processed = 'processed';
    case Failed = 'failed';
    case Dead = 'dead';
    case IgnoredStale = 'ignored_stale';

    public function isReplayable(): bool
    {
        return $this === self::Failed || $this === self::Dead;
    }
}
