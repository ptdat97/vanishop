<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts;

use Modules\Notification\Contracts\Data\OutgoingMessage;
use Modules\Notification\Contracts\Data\Recipient;
use Modules\Notification\Contracts\Data\SendResult;

/**
 * Extension point: kênh gửi tin. Core có `mail`; SMS brandname, Zalo ZNS, web push là plugin
 * (`contribute(NotificationChannel::TAG, …)`, có hiệu lực khi plugin bật).
 *
 * Tin được gửi bất đồng bộ, có retry: `send()` không ném exception cho lỗi dự kiến mà trả
 * `SendResult::retryable()` (timeout, 5xx, hết quota tạm thời) hoặc `permanent()` (số không hợp lệ, template bị từ chối).
 * `$message->idempotencyKey` ổn định giữa các lần thử — gửi kèm cho nhà cung cấp nếu họ hỗ trợ.
 */
interface NotificationChannel
{
    public const TAG = 'vani.notification.channels';

    public function code(): string;

    /** Người nhận có địa chỉ phù hợp với kênh (email cho mail, SĐT cho sms/zns). */
    public function canReach(Recipient $recipient): bool;

    public function send(OutgoingMessage $message): SendResult;
}
