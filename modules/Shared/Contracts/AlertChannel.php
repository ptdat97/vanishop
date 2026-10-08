<?php

declare(strict_types=1);

namespace Modules\Shared\Contracts;

use Modules\Shared\Contracts\Data\Alert;

/**
 * Extension point (0.3.30): kênh nhận cảnh báo vận hành (roadmap Phase 6). Core có kênh `mail` (VANI_ALERT_EMAILS);
 * plugin đóng góp kênh khác (Telegram, Slack, Zalo…) qua `contribute(AlertChannel::TAG, …)`.
 *
 * `send()` được gọi đồng bộ từ `vani:alerts:check` (scheduler) — không đẩy vào queue, vì queue có thể chính là thứ đang
 * hỏng. Lỗi của một kênh được cô lập (ghi log), các kênh khác vẫn nhận.
 */
interface AlertChannel
{
    public const TAG = 'vani.alert-channels';

    public function code(): string;

    public function send(Alert $alert): void;
}
