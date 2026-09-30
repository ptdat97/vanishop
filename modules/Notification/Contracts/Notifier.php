<?php

declare(strict_types=1);

namespace Modules\Notification\Contracts;

use Modules\Notification\Contracts\Data\NotificationRequest;

/**
 * Service contract: gửi một tin theo loại (`order_placed`…) tới người nhận, trên mọi kênh có template đang bật
 * cho brand và kênh đó liên lạc được với người nhận. Tin marketing chỉ gửi khi khách có consent theo brand × kênh.
 * Idempotent theo `NotificationRequest::$key` + kênh.
 */
interface Notifier
{
    /**
     * @return list<string> kênh đã xếp hàng gửi
     */
    public function notify(NotificationRequest $request): array;
}
