<?php

declare(strict_types=1);

namespace Modules\Notification\Application;

use Modules\Extension\Contracts\Extensions;
use Modules\Notification\Contracts\NotificationChannel;

/**
 * Kênh gửi tin đang có hiệu lực (Core + plugin kênh đang bật).
 */
final class ChannelRegistry
{
    public function __construct(private readonly Extensions $extensions) {}

    /**
     * @return array<string, NotificationChannel> code => channel
     */
    public function all(): array
    {
        return $this->extensions->implementations(NotificationChannel::TAG, NotificationChannel::class);
    }
}
